<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PricingSource;
use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'TT-'.now()->format('y').'-'.fake()->unique()->numerify('####'),
            'public_token_hash' => Booking::newPublicToken()[1],
            'status' => BookingStatus::New,
            'tour_id' => $tour = Tour::factory(),
            'operator_id' => fn (array $attributes) => Tour::find($attributes['tour_id'])->operator_id,
            'date_from' => $from = now(config('brand.timezone'))->addMonths(3)->startOfDay(),
            'date_to' => $from->copy()->addDays(2),
            'adults' => 2,
            'children' => 0,
            'pricing_source' => PricingSource::Private,
            'unit_price_cents' => 33000,
            'total_cents' => 66000,
            'commission_rate' => 15,
            'deposit_cents' => 9900,
            'balance_cents' => 56100,
            'customer_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'whatsapp' => '+49 151 '.fake()->numerify('#######'),
            'country' => 'DE',
            'terms_accepted_at' => now(),
            'terms_version' => '2027-01',
        ];
    }
}
