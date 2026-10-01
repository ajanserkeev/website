<?php

namespace App\Http\Resources\V1;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * {src, alt} for the site. src is the 1600 px WebP; the site's image loader swaps the -w1600 suffix
 * for -w480 / -w960 when a smaller size is enough (plan, recommendation 14).
 */
final class Photo
{
    /** @return array{src: string, alt: string} */
    public static function from(Media $media, string $fallbackAlt): array
    {
        return [
            'src' => $media->getAvailableUrl(['w1600']),
            'alt' => $media->getCustomProperty('alt') ?: $fallbackAlt,
        ];
    }

    /** @return array{src: string, alt: string}|null */
    public static function first(HasMedia $model, string $collection, string $fallbackAlt): ?array
    {
        $media = $model->getFirstMedia($collection);

        return $media ? self::from($media, $fallbackAlt) : null;
    }

    /** @return list<array{src: string, alt: string}> */
    public static function all(HasMedia $model, string $collection, string $fallbackAlt): array
    {
        return $model->getMedia($collection)->map(fn (Media $m) => self::from($m, $fallbackAlt))->values()->all();
    }
}
