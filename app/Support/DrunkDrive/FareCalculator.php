<?php

namespace App\Support\DrunkDrive;

/**
 * Distance-based fare in R (rupiah-style integer units).
 *
 * Anchors from product requirements:
 * - 1 km → 2000 R
 * - 5 km → 5000 R
 *
 * Interpretation: linear fare through those anchors (not literal km² — a pure
 * quadratic through the origin cannot hit both points). Equivalent forms:
 *
 *   fare_R(d) = round(1250 + 750 × d)
 *   fare_R(d) = round(2000 + 750 × max(0, d − 1))
 *
 * where d is distance in kilometres (d ≥ 0).
 */
class FareCalculator
{
    public const BASE_R = 1250;

    public const RATE_PER_KM_R = 750;

    public const ANCHOR_1KM_R = 2000;

    public const ANCHOR_5KM_R = 5000;

    /**
     * @return array{distance_km: float, fare_r: int, currency: string, formula: string, anchors: array}
     */
    public static function calculate(float $distanceKm): array
    {
        $distanceKm = max(0.0, $distanceKm);
        $fare = (int) round(self::BASE_R + self::RATE_PER_KM_R * $distanceKm);

        return [
            'distance_km' => round($distanceKm, 3),
            'fare_r' => $fare,
            'currency' => 'R',
            'formula' => 'fare_R(d) = round(1250 + 750 * d)  // d in km; 1km→2000R, 5km→5000R',
            'anchors' => [
                '1_km' => self::ANCHOR_1KM_R,
                '5_km' => self::ANCHOR_5KM_R,
            ],
        ];
    }

    public static function fareOnly(float $distanceKm): int
    {
        return self::calculate($distanceKm)['fare_r'];
    }
}
