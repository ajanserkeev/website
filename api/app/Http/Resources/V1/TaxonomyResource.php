<?php

namespace App\Http\Resources\V1;

use App\Models\Activity;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Region or activity with its cover and number of public tours (tours_count from withCount).
 *
 * @mixin Region|Activity
 */
class TaxonomyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'places' => $this->when($this->resource instanceof Region, fn () => $this->places ?? []),
            'hero' => Photo::first($this->resource, 'hero', $this->name),
            'tourCount' => (int) ($this->tours_count ?? 0),
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
        ];
    }
}
