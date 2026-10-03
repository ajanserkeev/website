<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PricingSource;
use App\Models\Booking;
use App\Models\Operator;
use App\Models\Tour;
use App\Models\User;
use App\Services\Booking\DepositCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Test accounts for the three roles and a year of invented bookings so the dashboards have numbers:
 * - partner@example.com / password → /partner as Naryn Nomad Routes;
 * - tourist@example.com → "Sign in as a test traveler" on the site; one completed trip to review.
 * Bookings are private-date ones, so group departures keep their free seats.
 */
class DemoAccountsSeeder extends Seeder
{
    private const TRAVELERS = [
        ['Anna Weber', 'DE'], ['James Carter', 'GB'], ['Sophie Martin', 'FR'], ['Lukas Novak', 'CZ'], ['Emma Johansson', 'SE'],
        ['Daniel Kim', 'KR'], ['Olivia Brown', 'US'], ['Marco Rossi', 'IT'], ['Yuki Tanaka', 'JP'], ['Noah Jansen', 'NL'],
        ['Chloe Dubois', 'FR'], ['Ethan Wright', 'AU'], ['Mia Schneider', 'CH'], ['Leo Fischer', 'AT'], ['Isabel Garcia', 'ES'],
    ];

    public function run(DepositCalculator $deposits): void
    {
        $partner = Operator::where('slug', 'naryn-nomad-routes')->firstOrFail();
        User::factory()->partner($partner)->create(['name' => 'Naryn Nomad Routes', 'email' => 'partner@example.com']);
        $tourist = User::factory()->tourist()->create(['name' => 'Test Traveler', 'email' => 'tourist@example.com', 'google_id' => null]);

        mt_srand(2027);
        $today = Carbon::now(config('brand.timezone'))->startOfDay();
        $tours = Tour::with(['privatePrices', 'departures'])->get();

        foreach ($tours as $tour) {
            $unit = $tour->privatePrices->first()?->price_per_person_cents ?? $tour->departures->first()?->price_cents ?? 30000;
            // From March this year to next summer; past trips are completed, future ones paid or waiting.
            foreach (range(1, mt_rand(7, 12)) as $i) {
                $start = $today->copy()->subMonths(7)->addDays(mt_rand(0, 330));
                $status = $start->isPast()
                    ? (mt_rand(1, 10) === 1 ? BookingStatus::CancelledByTourist : BookingStatus::Completed)
                    : [BookingStatus::VoucherSent, BookingStatus::VoucherSent, BookingStatus::DepositPaid, BookingStatus::AwaitingPayment, BookingStatus::New][mt_rand(0, 4)];
                $this->booking($tour, $start, mt_rand(1, 4), $unit, $status, $deposits);
            }
        }

        // The test traveler: a finished Song-Kul trek to review and a paid trip ahead.
        $songKul = $tours->firstWhere('slug', 'song-kul-horse-trek-yurt-stay');
        $alaKul = $tours->firstWhere('slug', 'ala-kul-lake-trek-from-karakol');
        foreach ([[$songKul, $today->copy()->subDays(40), BookingStatus::Completed], [$alaKul, $today->copy()->addDays(75), BookingStatus::VoucherSent]] as [$tour, $start, $status]) {
            $booking = $this->booking($tour, $start, 2, $tour->privatePrices->first()->price_per_person_cents, $status, $deposits);
            $booking->forceFill(['user_id' => $tourist->id, 'customer_name' => $tourist->name, 'email' => $tourist->email, 'country' => 'DE'])->save();
        }
    }

    private function booking(Tour $tour, Carbon $start, int $adults, int $unit, BookingStatus $status, DepositCalculator $deposits): Booking
    {
        [$name, $country] = self::TRAVELERS[mt_rand(0, count(self::TRAVELERS) - 1)];
        $total = $unit * $adults;
        $rate = $tour->effectiveCommissionRate();
        $deposit = $deposits->depositCents($total, $rate);
        $created = $start->copy()->subDays(mt_rand(20, 120));
        $paid = in_array($status, [BookingStatus::DepositPaid, BookingStatus::VoucherSent, BookingStatus::Completed], true)
            || ($status === BookingStatus::CancelledByTourist);

        $booking = new Booking([
            'customer_name' => $name,
            'email' => Str::slug($name, '.').'@example.com',
            'country' => $country,
            'adults' => $adults,
            'children' => 0,
        ]);
        $booking->forceFill([
            'code' => 'tmp-'.Str::lower(Str::random(14)),
            'status' => $status,
            'tour_id' => $tour->id,
            'operator_id' => $tour->operator_id,
            'date_from' => $start->toDateString(),
            'date_to' => $start->copy()->addDays($tour->duration_days - 1)->toDateString(),
            'pricing_source' => PricingSource::Private,
            'unit_price_cents' => $unit,
            'total_cents' => $total,
            'commission_rate' => $rate,
            'deposit_cents' => $deposit,
            'balance_cents' => $total - $deposit,
            'terms_accepted_at' => $created,
            'terms_version' => config('brand.terms_version'),
            'paid_at' => $paid ? $created->copy()->addDays(2) : null,
            'voucher_sent_at' => in_array($status, [BookingStatus::VoucherSent, BookingStatus::Completed], true) ? $created->copy()->addDays(2) : null,
            'cancelled_at' => $status === BookingStatus::CancelledByTourist ? $created->copy()->addDays(10) : null,
            'created_at' => $created,
            'updated_at' => $created,
        ]);
        $booking->setPublicToken(Str::random(40));
        $booking->save();
        $booking->forceFill(['code' => sprintf('%s-%s-%04d', config('brand.booking_prefix'), $created->format('y'), $booking->id)])->save();
        $booking->events()->create(['from_status' => null, 'to_status' => $status, 'note' => 'Demo booking']);

        return $booking;
    }
}
