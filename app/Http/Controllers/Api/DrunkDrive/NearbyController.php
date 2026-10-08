<?php

namespace App\Http\Controllers\Api\DrunkDrive;

use App\Http\Controllers\Api\Controller;
use App\Http\Responses\ApiResponses;
use App\Models\DrunkDrive\DriverProfile;
use App\Support\DrunkDrive\Haversine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearbyController extends Controller
{
    public function drivers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $radiusKm = (float) ($validated['radius_km'] ?? 5);
        $limit = (int) ($validated['limit'] ?? 20);

        // Bounding-box prefilter (approx) then exact Haversine
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / max(0.01, 111.0 * cos(deg2rad($lat)));

        $candidates = DriverProfile::query()
            ->with('user:id,name,email,status')
            ->where('status', 'approved')
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->whereBetween('current_latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('current_longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->get();

        $nearby = $candidates
            ->map(function (DriverProfile $profile) use ($lat, $lng) {
                $distance = Haversine::distanceKm(
                    $lat,
                    $lng,
                    (float) $profile->current_latitude,
                    (float) $profile->current_longitude
                );

                return [
                    'driver_id' => $profile->user_id,
                    'name' => $profile->user?->name,
                    'vehicle_type' => $profile->vehicle_type,
                    'vehicle_plate' => $profile->vehicle_plate,
                    'latitude' => $profile->current_latitude,
                    'longitude' => $profile->current_longitude,
                    'location_updated_at' => $profile->location_updated_at,
                    'distance_km' => round($distance, 3),
                ];
            })
            ->filter(fn (array $row) => $row['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->values()
            ->take($limit)
            ->all();

        return ApiResponses::ok('Nearby available drivers', [
            'origin' => ['latitude' => $lat, 'longitude' => $lng],
            'radius_km' => $radiusKm,
            'count' => count($nearby),
            'drivers' => $nearby,
        ]);
    }
}
