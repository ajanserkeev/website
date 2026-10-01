<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->city(),
            'slug' => Str::slug($name),
            'summary' => fake()->sentence(),
            'places' => [fake()->city(), fake()->city()],
        ];
    }
}
