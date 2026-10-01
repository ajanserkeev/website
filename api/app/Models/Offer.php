<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Tailor-made proposal created by staff from an inquiry; becomes a booking when accepted (step 4.10). */
#[Guarded(['id'])]
class Offer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'commission_rate' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}
