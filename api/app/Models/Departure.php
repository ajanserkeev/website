<?php

namespace App\Models;

use App\Enums\DepartureStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A group departure date. seats_booked changes only inside a locked transaction (plan, recommendation 7). */
#[Guarded(['id'])]
class Departure extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => DepartureStatus::class,
        ];
    }

    public function seatsLeft(): int
    {
        return max($this->seats_total - $this->seats_booked, 0);
    }

    public function isBookable(int $travelers = 1): bool
    {
        return in_array($this->status, [DepartureStatus::Open, DepartureStatus::Guaranteed], true)
            && $this->seatsLeft() >= $travelers;
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
