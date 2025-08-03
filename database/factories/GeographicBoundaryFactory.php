<?php

namespace Database\Factories;

use App\Models\GeographicBoundary;
use App\Models\AdministrativeRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeographicBoundary>
 */
class GeographicBoundaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lat = $this->faker->latitude(-11, 6);
        $lng = $this->faker->longitude(95, 141);
        
        return [
            'region_id' => AdministrativeRegion::factory(),
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [$lng - 0.01, $lat - 0.01],
                    [$lng + 0.01, $lat - 0.01],
                    [$lng + 0.01, $lat + 0.01],
                    [$lng - 0.01, $lat + 0.01],
                    [$lng - 0.01, $lat - 0.01],
                ]],
            ],
            'centroid' => [
                'latitude' => $lat,
                'longitude' => $lng,
            ],
            'bbox' => [
                'min_lat' => $lat - 0.01,
                'min_lng' => $lng - 0.01,
                'max_lat' => $lat + 0.01,
                'max_lng' => $lng + 0.01,
            ],
            'precision' => $this->faker->randomElement(['high', 'medium', 'low']),
            'source' => $this->faker->randomElement(['official', 'openstreetmap', 'sample_data']),
        ];
    }

    /**
     * Indicate that the boundary has high precision.
     */
    public function highPrecision(): static
    {
        return $this->state(fn (array $attributes) => [
            'precision' => 'high',
        ]);
    }

    /**
     * Indicate that the boundary has medium precision.
     */
    public function mediumPrecision(): static
    {
        return $this->state(fn (array $attributes) => [
            'precision' => 'medium',
        ]);
    }

    /**
     * Indicate that the boundary has low precision.
     */
    public function lowPrecision(): static
    {
        return $this->state(fn (array $attributes) => [
            'precision' => 'low',
        ]);
    }
}