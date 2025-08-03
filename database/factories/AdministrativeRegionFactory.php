<?php

namespace Database\Factories;

use App\Models\AdministrativeRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdministrativeRegion>
 */
class AdministrativeRegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['provinsi', 'kabupaten', 'kota', 'kecamatan', 'kelurahan', 'desa'];
        $type = $this->faker->randomElement($types);
        $name = $this->faker->unique()->city();
        
        return [
            'id' => strtolower(str_replace(' ', '-', $name)),
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'type' => $type,
            'code' => $this->faker->unique()->numerify('####'),
            'parent_id' => null,
            'postal_codes' => $this->faker->randomElements(['10110', '10120', '10130', '10140'], $this->faker->numberBetween(1, 3)),
            'coordinates' => [
                'latitude' => $this->faker->latitude(-11, 6),
                'longitude' => $this->faker->longitude(95, 141),
            ],
            'area' => $this->faker->randomFloat(2, 1, 50000),
            'population' => $this->faker->numberBetween(1000, 50000000),
            'description' => $this->faker->sentence(),
            'dataset_url' => $this->faker->optional()->url(),
        ];
    }

    /**
     * Indicate that the region is a province.
     */
    public function province(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'provinsi',
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the region is a regency.
     */
    public function regency(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'kabupaten',
        ]);
    }

    /**
     * Indicate that the region is a city.
     */
    public function city(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'kota',
        ]);
    }

    /**
     * Indicate that the region is a district.
     */
    public function district(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'kecamatan',
        ]);
    }

    /**
     * Indicate that the region is a village.
     */
    public function village(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $this->faker->randomElement(['kelurahan', 'desa']),
        ]);
    }
}