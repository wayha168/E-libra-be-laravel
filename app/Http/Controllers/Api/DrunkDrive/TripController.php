<?php

namespace App\Http\Controllers\Api\DrunkDrive;

use App\Http\Controllers\Api\Controller;
use App\Http\Responses\ApiResponses;
use App\Models\DrunkDrive\Trip;
use App\Support\DrunkDrive\FareCalculator;
use App\Support\DrunkDrive\Haversine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TripController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'dropoff_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['nullable', 'string', 'max:255'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'store_id' => ['nullable', 'uuid', 'exists:dd_stores,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'driver_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $distance = $validated['distance_km'] ?? null;
        if (
            $distance === null
            && isset($validated['dropoff_latitude'], $validated['dropoff_longitude'])
        ) {
            $distance = Haversine::distanceKm(
                (float) $validated['pickup_latitude'],
                (float) $validated['pickup_longitude'],
                (float) $validated['dropoff_latitude'],
                (float) $validated['dropoff_longitude']
            );
        }

        $fare = $distance !== null ? FareCalculator::fareOnly((float) $distance) : null;

        $trip = Trip::create([
            'user_id' => $request->user()->id,
            'driver_id' => $validated['driver_id'] ?? null,
            'store_id' => $validated['store_id'] ?? null,
            'status' => 'requested',
            'pickup_latitude' => $validated['pickup_latitude'],
            'pickup_longitude' => $validated['pickup_longitude'],
            'pickup_address' => $validated['pickup_address'] ?? null,
            'dropoff_latitude' => $validated['dropoff_latitude'] ?? null,
            'dropoff_longitude' => $validated['dropoff_longitude'] ?? null,
            'dropoff_address' => $validated['dropoff_address'] ?? null,
            'distance_km' => $distance !== null ? round((float) $distance, 3) : null,
            'fare_r' => $fare,
            'currency' => 'R',
            'notes' => $validated['notes'] ?? null,
            'requested_at' => now(),
        ]);

        return ApiResponses::created('Trip requested', $trip->load(['rider:id,name,email', 'driver:id,name,email', 'store']));
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Trip::query()
            ->with(['rider:id,name,email', 'driver:id,name,email', 'store:id,name,slug'])
            ->orderByDesc('requested_at')
            ->orderByDesc('created_at');

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->string('user_id'));
            }
            if ($request->filled('driver_id')) {
                $query->where('driver_id', $request->string('driver_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
        } elseif ($user->isDriver()) {
            $query->where(function ($q) use ($user) {
                $q->where('driver_id', $user->id)->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        return ApiResponses::ok('Trip history', $query->paginate(20));
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();

        if (
            !$user->isAdmin()
            && !$user->isSuperAdmin()
            && $trip->user_id !== $user->id
            && $trip->driver_id !== $user->id
        ) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return ApiResponses::ok('Trip fetched', $trip->load(['rider:id,name,email', 'driver:id,name,email', 'store']));
    }

    public function updateStatus(Request $request, Trip $trip): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['requested', 'accepted', 'in_progress', 'completed', 'cancelled'])],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        $isStaff = $user->isAdmin() || $user->isSuperAdmin();
        $isAssignedDriver = $trip->driver_id === $user->id;
        $isRider = $trip->user_id === $user->id;

        if (!$isStaff && !$isAssignedDriver && !($isRider && $validated['status'] === 'cancelled')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = $validated['status'];
        $updates = ['status' => $status];

        if ($status === 'accepted') {
            $updates['accepted_at'] = now();
            if (!$trip->driver_id && $user->isDriver()) {
                $updates['driver_id'] = $user->id;
            }
        } elseif ($status === 'in_progress') {
            $updates['started_at'] = now();
        } elseif ($status === 'completed') {
            $updates['completed_at'] = now();
            if (isset($validated['distance_km'])) {
                $updates['distance_km'] = round((float) $validated['distance_km'], 3);
                $updates['fare_r'] = FareCalculator::fareOnly((float) $validated['distance_km']);
            } elseif ($trip->distance_km !== null && $trip->fare_r === null) {
                $updates['fare_r'] = FareCalculator::fareOnly((float) $trip->distance_km);
            }
        } elseif ($status === 'cancelled') {
            $updates['cancelled_at'] = now();
        }

        $trip->update($updates);

        return ApiResponses::ok('Trip status updated', $trip->fresh()->load(['rider:id,name,email', 'driver:id,name,email', 'store']));
    }
}
