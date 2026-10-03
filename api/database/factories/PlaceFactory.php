<?php

namespace Database\Factories;

use App\Enums\PlaceKind;
use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = Str::title(fake()->unique()->words(2, true)).' Lake',
            'slug' => Str::slug($name),
            'kind' => PlaceKind::Lake,
            // Inside Kyrgyzstan.
            'latitude' => fake()->randomFloat(6, 40.5, 42.8),
            'longitude' => fake()->randomFloat(6, 72.0, 79.5),
            'altitude_m' => fake()->numberBetween(700, 4000),
            'summary' => fake()->sentence(),
            'is_published' => true,
        ];
    }
}
