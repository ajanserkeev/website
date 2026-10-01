<?php

namespace Database\Factories;

use App\Models\PrivatePrice;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivatePrice>
 */
class PrivatePriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'group_size_from' => 2,
            'group_size_to' => 4,
            'price_per_person_cents' => 45000,
        ];
    }
}
