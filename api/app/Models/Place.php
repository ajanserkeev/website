<?php

namespace App\Models;

use App\Enums\PlaceKind;
use App\Models\Concerns\HasWebpImages;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/** Point of interest on the maps. Tours reach it through their days (tour_days.place_id). */
#[Guarded(['id'])]
class Place extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasWebpImages;

    protected function casts(): array
    {
        return [
            'kind' => PlaceKind::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->acceptsMimeTypes(self::IMAGE_MIME_TYPES)
            ->singleFile();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate()->preventOverwrite();
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(TourDay::class);
    }
}
