<?php

namespace App\Http\Resources\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Travel guide. The list endpoint omits sections; the detail adds sections, facts and tour cards.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'hero' => Photo::first($this->resource, 'hero', $this->title),
            'readingMinutes' => $this->reading_minutes,
            'updatedOn' => ($this->updated_at > $this->published_at ? $this->updated_at : $this->published_at)?->toDateString(),
            'facts' => $this->facts ?? [],
            'sections' => $this->when($this->relationLoaded('tours'), fn () => $this->sections ?? []),
            'tours' => TourSummaryResource::collection($this->whenLoaded('tours')),
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
        ];
    }
}
