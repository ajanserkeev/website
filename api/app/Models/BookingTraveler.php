<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Traveler names for the operator's booking sheet (launch document, section 11). */
#[Fillable(['name', 'is_child', 'sort'])]
class BookingTraveler extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_child' => 'boolean'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
