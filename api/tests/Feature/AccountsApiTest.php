<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\PrivatePrice;
use App\Models\Review;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    config(['services.dev_login' => true, 'services.google.client_id' => 'client', 'services.google.client_secret' => 'secret']);
});

function signIn(?User $user = null): array
{
    $user ??= User::factory()->tourist()->create();

    return ['Authorization' => 'Bearer '.$user->createToken('site')->plainTextToken];
}

function fakeGoogle(string $email = 'anna@example.com', string $id = '1001'): void
{
    $profile = (new GoogleUser)->map(['id' => $id, 'name' => 'Anna Weber', 'email' => $email, 'avatar' => 'https://lh3.googleusercontent.com/a/anna']);
    Socialite::shouldReceive('driver->stateless->user')->andReturn($profile);
}

it('tells the login page which sign-in methods work', function () {
    $this->getJson('/api/v1/auth/providers')->assertJsonPath('data', ['google' => true, 'dev' => false]);

    app()['env'] = 'local';
    $this->getJson('/api/v1/auth/providers')->assertJsonPath('data.dev', true);
});

it('signs a traveler in with Google and links their earlier bookings', function () {
    $earlier = Booking::factory()->create(['email' => 'Anna@Example.com']);
    $someoneElse = Booking::factory()->create(['email' => 'bob@example.com']);
    fakeGoogle();

    $response = $this->postJson('/api/v1/auth/google', ['code' => 'from-google'])->assertOk()
        ->assertJsonPath('data.user', ['name' => 'Anna Weber', 'email' => 'anna@example.com', 'avatarUrl' => 'https://lh3.googleusercontent.com/a/anna']);

    $user = User::where('google_id', '1001')->firstOrFail();
    expect($user->role)->toBe(UserRole::Tourist)
        ->and($user->password)->toBeNull()
        ->and($earlier->fresh()->user_id)->toBe($user->id)
        ->and($someoneElse->fresh()->user_id)->toBeNull();

    $this->withToken($response->json('data.token'))->getJson('/api/v1/me')->assertJsonPath('data.email', 'anna@example.com');

    // The second sign-in finds the same account.
    fakeGoogle();
    $this->postJson('/api/v1/auth/google', ['code' => 'again'])->assertOk();
    expect(User::count())->toBe(1);
});

it('does not open a traveler session for team or partner emails', function () {
    User::factory()->create(['email' => 'anna@example.com', 'role' => UserRole::Admin]);
    fakeGoogle();

    $this->postJson('/api/v1/auth/google', ['code' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('builds the Google URL with the site\'s state and callback', function () {
    $url = $this->getJson('/api/v1/auth/google/url?state='.str_repeat('s', 32))->assertOk()->json('data.url');

    expect($url)->toStartWith('https://accounts.google.com/o/oauth2/auth')
        ->toContain('state='.str_repeat('s', 32))
        ->toContain(urlencode('http://localhost:3000/auth/google/callback'));
});

it('offers the test sign-in only locally', function () {
    $this->postJson('/api/v1/auth/dev-login', ['email' => 'test@example.com'])->assertNotFound();

    app()['env'] = 'local';
    $this->postJson('/api/v1/auth/dev-login', ['email' => 'test@example.com', 'name' => 'Test Traveler'])->assertOk()->assertJsonStructure(['data' => ['token']]);
    expect(User::where('email', 'test@example.com')->first()->role)->toBe(UserRole::Tourist);
});

it('requires a token for the account and logs out', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();

    $headers = signIn();
    $this->getJson('/api/v1/me', $headers)->assertOk();
    $this->postJson('/api/v1/auth/logout', [], $headers)->assertOk();
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/v1/me', $headers)->assertUnauthorized();
});

it('saves the booking to the account of a signed-in traveler', function () {
    $user = User::factory()->tourist()->create();
    $tour = Tour::factory()->create();
    PrivatePrice::factory()->for($tour)->create(['group_size_from' => 1, 'group_size_to' => 6, 'price_per_person_cents' => 40000]);

    $this->postJson('/api/v1/bookings', [
        'tour' => $tour->slug,
        'date_from' => now(config('brand.timezone'))->addDays(40)->toDateString(),
        'adults' => 2,
        'customer_name' => 'Anna Weber',
        'email' => 'anna@example.com',
        'terms_accepted' => true,
    ], signIn($user))->assertCreated();

    $this->getJson('/api/v1/me/bookings', signIn($user))->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tour.slug', $tour->slug)
        ->assertJsonPath('data.0.canReview', false);
});

it('keeps saved tours on the account', function () {
    $user = User::factory()->tourist()->create();
    $tour = Tour::factory()->create(['title' => 'Ala-Kul Trek']);
    $headers = signIn($user);

    $this->putJson("/api/v1/me/saved-tours/{$tour->slug}", [], $headers)->assertOk();
    $this->putJson("/api/v1/me/saved-tours/{$tour->slug}", [], $headers)->assertOk();
    $this->getJson('/api/v1/me/saved-tours', $headers)->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Ala-Kul Trek');
    $this->putJson('/api/v1/me/saved-tours/unknown', [], $headers)->assertNotFound();

    $this->deleteJson("/api/v1/me/saved-tours/{$tour->slug}", [], $headers)->assertOk();
    $this->getJson('/api/v1/me/saved-tours', $headers)->assertJsonCount(0, 'data');
});

it('takes one review per completed trip and publishes it with a short name', function () {
    $user = User::factory()->tourist()->create(['name' => 'Anna Weber']);
    $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => BookingStatus::Completed, 'country' => 'DE']);
    $headers = signIn($user);
    $review = ['rating' => 5, 'title' => 'Best week', 'body' => 'Three days on horseback with a wonderful host family at the lake.'];

    $this->getJson('/api/v1/me/bookings', $headers)->assertJsonPath('data.0.canReview', true);
    $this->postJson("/api/v1/me/bookings/{$booking->code}/review", $review, $headers)->assertCreated();
    $this->postJson("/api/v1/me/bookings/{$booking->code}/review", $review, $headers)->assertUnprocessable();

    expect(Review::firstOrFail())
        ->author_name->toBe('Anna W.')
        ->booking_id->toBe($booking->id)
        ->tour_id->toBe($booking->tour_id)
        ->is_published->toBeTrue()
        ->trip_month->toBe($booking->date_from->format('Y-m'));
    $this->getJson('/api/v1/me/bookings', $headers)->assertJsonPath('data.0.canReview', false)->assertJsonPath('data.0.review.rating', 5);
    $this->getJson("/api/v1/tours/{$booking->tour->slug}")->assertJsonPath('data.reviews.0.verifiedBooking', true);
});

it('refuses reviews before the trip is over or for other people\'s trips', function (BookingStatus $status, bool $own, int $code) {
    $user = User::factory()->tourist()->create();
    $booking = Booking::factory()->create(['user_id' => $own ? $user->id : User::factory()->tourist()->create()->id, 'status' => $status]);

    $this->postJson("/api/v1/me/bookings/{$booking->code}/review", ['rating' => 4, 'body' => str_repeat('Great trip. ', 4)], signIn($user))
        ->assertStatus($code);
})->with([
    'trip ahead' => [BookingStatus::VoucherSent, true, 422],
    'cancelled' => [BookingStatus::CancelledByTourist, true, 422],
    'someone else\'s' => [BookingStatus::Completed, false, 404],
]);
