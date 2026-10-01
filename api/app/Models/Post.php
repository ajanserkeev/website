<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/** A travel guide with tour cards inside (launch document, section 09). */
#[Guarded(['id'])]
class Post extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasWebpImages;

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'facts' => 'array',
            'published_at' => 'datetime',
        ];
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

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class)->withPivot('sort')->orderByPivot('sort');
    }
}
