<?php

namespace Database\Factories;

use App\Enums\TourStatus;
use App\Enums\TourType;
use App\Models\Operator;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operator_id' => Operator::factory(),
            'type' => TourType::MultiDay,
            'status' => TourStatus::Published,
            'title' => $title = fake()->unique()->sentence(4),
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'duration_days' => 3,
            'difficulty' => 3,
            'group_size_min' => 2,
            'group_size_max' => 8,
            'guide_languages' => ['English', 'Russian'],
            'season_from' => 6,
            'season_to' => 9,
            'has_group_dates' => true,
            'has_private_option' => false,
            'published_at' => now(),
        ];
    }
}
