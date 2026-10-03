<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\ReviewSource;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MeResource;
use App\Http\Resources\V1\Photo;
use App\Http\Resources\V1\TourSummaryResource;
use App\Models\Booking;
use App\Models\Review;
use App\Services\Catalog\TourCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** The signed-in traveler's account on the site: bookings, saved tours and reviews of completed trips. */
class AccountController extends Controller
{
    public function __construct(private readonly TourCatalog $catalog) {}

    public function me(Request $request): MeResource
    {
        return new MeResource($request->user());
    }

    public function bookings(Request $request): JsonResponse
    {
        $bookings = $request->user()->bookings()->with(['tour.media', 'review'])->orderByDesc('date_from')->get();

        return response()->json(['data' => $bookings->map(fn (Booking $b) => [
            'code' => $b->code,
            'status' => $b->status->value,
            'statusLabel' => $b->status->label(),
            'tour' => ['slug' => $b->tour->slug, 'title' => $b->tour->title, 'image' => Photo::first($b->tour, 'gallery', $b->tour->title)],
            'dateFrom' => $b->date_from->toDateString(),
            'dateTo' => $b->date_to->toDateString(),
            'travelers' => $b->travelerCount(),
            'totalCents' => $b->total_cents,
            'manageUrl' => $b->myBookingUrl(),
            'canReview' => $b->status === BookingStatus::Completed && ! $b->review,
            'review' => $b->review ? [
                'rating' => $b->review->rating,
                'title' => $b->review->title,
                'body' => $b->review->body,
                'published' => $b->review->is_published,
            ] : null,
        ])]);
    }

    public function savedTours(Request $request): AnonymousResourceCollection
    {
        $ids = $request->user()->savedTours()->pluck('tours.id');

        return TourSummaryResource::collection($this->catalog->query()->whereIn('id', $ids)->get());
    }

    public function saveTour(Request $request, string $slug): JsonResponse
    {
        $tour = $this->catalog->query()->where('slug', $slug)->first() ?? abort(404);
        $request->user()->savedTours()->syncWithoutDetaching([$tour->id]);

        return response()->json(['data' => ['saved' => true]]);
    }

    public function unsaveTour(Request $request, string $slug): JsonResponse
    {
        $request->user()->savedTours()->whereIn('tours.id', fn ($q) => $q->select('id')->from('tours')->where('slug', $slug))->detach();

        return response()->json(['data' => ['saved' => false]]);
    }

    /** Only for the traveler's own completed trip, once (reviews.booking_id is unique). */
    public function review(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:30', 'max:3000'],
        ]);
        $booking = $request->user()->bookings()->with('review')->where('code', $code)->first() ?? abort(404);

        if ($booking->status !== BookingStatus::Completed) {
            throw ValidationException::withMessages(['rating' => 'You can review a tour after the trip is completed.']);
        }
        if ($booking->review) {
            throw ValidationException::withMessages(['rating' => 'You have already reviewed this trip.']);
        }

        $review = Review::create([
            'tour_id' => $booking->tour_id,
            'operator_id' => $booking->operator_id,
            'booking_id' => $booking->id,
            'source' => ReviewSource::Site,
            'author_name' => self::shortName($request->user()->name),
            'country' => $booking->country,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'trip_month' => $booking->date_from->format('Y-m'),
            // A verified booking: published at once, the team can hide it in the admin.
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json(['data' => ['rating' => $review->rating, 'published' => true]], 201);
    }

    /** "Anna Weber" → "Anna W.": travelers' full names are not shown publicly. */
    private static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) < 2) {
            return $name;
        }

        return $parts[0].' '.Str::upper(Str::substr(end($parts), 0, 1)).'.';
    }
}
