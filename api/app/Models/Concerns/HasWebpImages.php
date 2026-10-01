<?php

namespace App\Models\Concerns;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photos with WebP versions for the site (step 4.4): 480 for cards on phones, 960 for cards and thumbnails,
 * 1600 for hero images and galleries. The originals are kept for later re-encoding.
 */
trait HasWebpImages
{
    use InteractsWithMedia;

    public const IMAGE_WIDTHS = [480, 960, 1600];

    public const IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (self::IMAGE_WIDTHS as $width) {
            $this->addMediaConversion("w{$width}")
                ->format('webp')
                ->quality(78)
                ->width($width);
        }
    }

    /** Alt text set in the admin, falling back to the record's title. */
    public function imageAlt(Media $media): string
    {
        return $media->getCustomProperty('alt') ?: (string) ($this->title ?? $this->name ?? '');
    }
}
