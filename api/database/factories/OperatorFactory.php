<?php

namespace Database\Factories;

use App\Models\Operator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Operator>
 */
class OperatorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->company().' Tours',
            'slug' => Str::slug($name),
            'description' => fake()->sentence(16),
            'base_city' => fake()->randomElement(['Bishkek', 'Karakol', 'Kochkor', 'Naryn', 'Osh']),
            'founded_year' => fake()->numberBetween(2000, 2022),
            'commission_rate' => 15,
            'contact_name' => fake()->firstName(),
            'phone' => '+996 555 '.fake()->numerify('######'),
            'whatsapp' => '+996 555 '.fake()->numerify('######'),
            'email' => fake()->unique()->companyEmail(),
            'google_rating' => 4.8,
            'google_reviews' => fake()->numberBetween(10, 300),
            'is_active' => true,
        ];
    }
}
