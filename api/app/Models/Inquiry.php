<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A question about a tour or a "Plan my trip" request from the site or the AI assistant. */
#[Fillable(['name', 'email', 'whatsapp', 'country', 'message', 'answers'])]
class Inquiry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'status' => InquiryStatus::class,
            'answers' => 'array',
            'utm' => 'array',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
