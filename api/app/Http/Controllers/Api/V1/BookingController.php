<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MyBookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingWorkflow;
use App\Services\Booking\InvalidBookingAction;
use App\Services\Catalog\TourCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Booking request and the traveler's "My booking" page (steps 4.6, 5.6, 5.7). No accounts: a secret link. */
class BookingController extends Controller
{
    public function __construct(private readonly BookingWorkflow $workflow, private readonly TourCatalog $catalog) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tour' => ['required', 'string'],
            'departure_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'required_without:departure_id'],
            'adults' => ['required', 'integer', 'min:1', 'max:30'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'size:2'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'travelers' => ['nullable', 'array', 'max:30'],
            'travelers.*' => ['nullable', 'string', 'max:120'],
            'terms_accepted' => ['accepted'],
            'utm' => ['nullable', 'array'],
            'utm.*' => ['nullable', 'string', 'max:200'],
        ]);

        $tour = $this->catalog->query()->with('privatePrices')->where('slug', $data['tour'])->first() ?? abort(404);
        // Signed-in travelers (Sanctum token forwarded by the site) see the booking in their account.
        [$booking, $token] = $this->workflow->request($tour, $data, $request->ip(), $request->user('sanctum'));

        return response()->json(['data' => ['code' => $booking->code, 'token' => $token, 'status' => $booking->status->value]], 201);
    }

    public function show(string $token): MyBookingResource
    {
        return new MyBookingResource($this->find($token)->load(['tour.media', 'operator', 'travelers', 'events']));
    }

    public function cancel(Request $request, string $token): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $booking = $this->find($token);
        if ($booking->date_from->isPast()) {
            abort(422, 'This trip has already started.');
        }

        try {
            $refund = $this->workflow->cancelByTourist($booking, $data['reason'] ?? null);
        } catch (InvalidBookingAction $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(['data' => ['status' => $booking->fresh()->status->value, 'refundCents' => $refund]]);
    }

    private function find(string $token): Booking
    {
        return Booking::findByPublicToken($token) ?? abort(404);
    }
}
