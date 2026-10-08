<?php

namespace Tests\Unit\DrunkDrive;

use App\Support\DrunkDrive\FareCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FareCalculatorTest extends TestCase
{
    public function test_anchors_match_product_examples(): void
    {
        $this->assertSame(2000, FareCalculator::fareOnly(1));
        $this->assertSame(5000, FareCalculator::fareOnly(5));
    }

    #[DataProvider('distanceProvider')]
    public function test_linear_formula(float $km, int $expected): void
    {
        $this->assertSame($expected, FareCalculator::fareOnly($km));
    }

    public static function distanceProvider(): array
    {
        return [
            'zero' => [0.0, 1250],
            'half_km' => [0.5, 1625],
            'two_km' => [2.0, 2750],
            'ten_km' => [10.0, 8750],
        ];
    }

    public function test_calculate_payload_includes_formula(): void
    {
        $quote = FareCalculator::calculate(1);

        $this->assertSame(2000, $quote['fare_r']);
        $this->assertSame('R', $quote['currency']);
        $this->assertArrayHasKey('formula', $quote);
        $this->assertSame(2000, $quote['anchors']['1_km']);
        $this->assertSame(5000, $quote['anchors']['5_km']);
    }
}
