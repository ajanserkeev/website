<?php

namespace App\Models;

use App\Enums\ReviewSource;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Review extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'source' => ReviewSource::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** "Verified booking" badge: the review is tied to a real completed booking. */
    public function isVerifiedBooking(): bool
    {
        return $this->booking_id !== null;
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
