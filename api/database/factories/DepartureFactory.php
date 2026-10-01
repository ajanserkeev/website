<?php

namespace Database\Factories;

use App\Enums\DepartureStatus;
use App\Models\Departure;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departure>
 */
class DepartureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'starts_on' => $start = now(config('brand.timezone'))->addMonths(3)->startOfDay(),
            'ends_on' => $start->copy()->addDays(2),
            'price_cents' => 33000,
            'seats_total' => 8,
            'seats_booked' => 0,
            'status' => DepartureStatus::Open,
        ];
    }
}
