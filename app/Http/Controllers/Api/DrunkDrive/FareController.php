<?php

namespace App\Http\Controllers\Api\DrunkDrive;

use App\Http\Controllers\Api\Controller;
use App\Http\Responses\ApiResponses;
use App\Support\DrunkDrive\FareCalculator;
use App\Support\DrunkDrive\Haversine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FareController extends Controller
{
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $distance = $validated['distance_km'] ?? null;

        if ($distance === null) {
            $required = ['pickup_latitude', 'pickup_longitude', 'dropoff_latitude', 'dropoff_longitude'];
            foreach ($required as $field) {
                if (!array_key_exists($field, $validated) || $validated[$field] === null) {
                    return response()->json([
                        'message' => 'Provide distance_km or pickup/dropoff lat/lng pair.',
                    ], 422);
                }
            }

            $distance = Haversine::distanceKm(
                (float) $validated['pickup_latitude'],
                (float) $validated['pickup_longitude'],
                (float) $validated['dropoff_latitude'],
                (float) $validated['dropoff_longitude']
            );
        }

        return ApiResponses::ok('Fare quote', FareCalculator::calculate((float) $distance));
    }

    public function formula(): JsonResponse
    {
        return ApiResponses::ok('DrunkDrive fare formula', [
            'formula' => 'fare_R(d) = round(1250 + 750 * d)',
            'equivalent' => 'fare_R(d) = round(2000 + 750 * max(0, d - 1))',
            'units' => 'd = kilometres; fare in R (integer)',
            'anchors' => [
                '1_km' => FareCalculator::ANCHOR_1KM_R,
                '5_km' => FareCalculator::ANCHOR_5KM_R,
            ],
            'note' => 'Product said “Km^2”; a pure quadratic through the origin cannot match both anchors, so a linear fit through 1km→2000R and 5km→5000R is used.',
        ]);
    }
}
