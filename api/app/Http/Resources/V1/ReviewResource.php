<?php

namespace App\Http\Resources\V1;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'author' => $this->author_name,
            'country' => $this->country,
            'rating' => $this->rating,
            'body' => $this->body,
            'tripMonth' => $this->trip_month,
            // "Verified booking" only when the review is tied to a real booking.
            'verifiedBooking' => $this->isVerifiedBooking(),
            'tour' => $this->whenLoaded('tour', fn () => ['slug' => $this->tour->slug, 'title' => $this->tour->title]),
        ];
    }
}
