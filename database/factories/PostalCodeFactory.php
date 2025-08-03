<?php

namespace Database\Factories;

use App\Models\PostalCode;
use App\Models\AdministrativeRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostalCode>
 */
class PostalCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('#####'),
            'region_id' => AdministrativeRegion::factory(),
            'area_name' => $this->faker->city(),
            'coordinates' => [
                'latitude' => $this->faker->latitude(-11, 6),
                'longitude' => $this->faker->longitude(95, 141),
            ],
            'delivery_office' => $this->faker->optional()->company(),
        ];
    }

    /**
     * Indicate that the postal code is for Jakarta.
     */
    public function jakarta(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $this->faker->unique()->numerify('1####'),
            'area_name' => $this->faker->randomElement(['Jakarta Pusat', 'Jakarta Barat', 'Jakarta Utara', 'Jakarta Selatan', 'Jakarta Timur']),
            'coordinates' => [
                'latitude' => $this->faker->latitude(-6.3, -6.1),
                'longitude' => $this->faker->longitude(106.7, 106.9),
            ],
        ]);
    }

    /**
     * Indicate that the postal code is for Bandung.
     */
    public function bandung(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $this->faker->unique()->numerify('4####'),
            'area_name' => 'Bandung',
            'coordinates' => [
                'latitude' => $this->faker->latitude(-6.95, -6.85),
                'longitude' => $this->faker->longitude(107.55, 107.65),
            ],
        ]);
    }
}