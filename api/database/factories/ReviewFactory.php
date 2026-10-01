<?php

namespace Database\Factories;

use App\Enums\ReviewSource;
use App\Models\Review;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tour_id' => $tour = Tour::factory(),
            'operator_id' => fn (array $attributes) => Tour::find($attributes['tour_id'])->operator_id,
            'source' => ReviewSource::Site,
            'author_name' => fake()->firstName().' '.fake()->randomLetter().'.',
            'country' => fake()->country(),
            'rating' => 5,
            'body' => fake()->paragraph(),
            'trip_month' => now()->subMonth()->format('Y-m'),
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
