<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/** Curated list of tours ("Horse treks", "Silk Road"), also an SEO landing page. */
#[Guarded(['id'])]
class Collection extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasWebpImages;

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')
            ->acceptsMimeTypes(self::IMAGE_MIME_TYPES)
            ->singleFile();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate()->preventOverwrite();
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class)->withPivot('sort')->orderByPivot('sort');
    }
}
