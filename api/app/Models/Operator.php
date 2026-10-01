<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * A local tour company. Its contacts are revealed to a traveler only in the voucher after deposit_paid,
 * so they are hidden from serialization (plan, recommendation 9).
 */
#[Guarded(['id'])]
#[Hidden(['contact_name', 'phone', 'whatsapp', 'email', 'notes', 'contract_signed_at'])]
class Operator extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasWebpImages, SoftDeletes;

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'tripadvisor_rating' => 'decimal:1',
            'google_rating' => 'decimal:1',
            'contract_signed_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->acceptsMimeTypes(self::IMAGE_MIME_TYPES)
            ->singleFile();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate()->preventOverwrite();
    }

    public function guides(): HasMany
    {
        return $this->hasMany(OperatorGuide::class)->orderBy('sort');
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
