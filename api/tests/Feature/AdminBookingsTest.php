<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Mail\BookingMail;
use App\Models\Departure;
use App\Models\Tour;
use App\Models\User;
use App\Services\Booking\BookingWorkflow;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    $this->actingAs(User::factory()->create(['role' => UserRole::Manager]));
    $tour = Tour::factory()->create();
    $this->departure = Departure::factory()->for($tour)->create(['seats_total' => 8, 'seats_booked' => 0]);
    [$this->booking] = app(BookingWorkflow::class)->request($tour, [
        'departure_id' => $this->departure->id,
        'adults' => 2,
        'customer_name' => 'Anna Weber',
        'email' => 'anna@example.com',
    ]);
});

it('lists bookings and opens a booking card', function () {
    $this->get('/admin/bookings')->assertOk()->assertSee($this->booking->code);
    $this->get("/admin/bookings/{$this->booking->id}")->assertOk()->assertSee('Anna Weber')->assertSee('Новый запрос');
});

it('confirms with a payment link, then marks the deposit as paid', function () {
    Livewire::test(ViewBooking::class, ['record' => $this->booking->getRouteKey()])
        ->callAction('confirm', data: ['payment_link_url' => 'https://pay.example/abc'])
        ->assertNotified('Письмо со ссылкой на оплату отправлено');

    expect($this->booking->fresh()->status)->toBe(BookingStatus::AwaitingPayment)
        ->and($this->departure->fresh()->seats_booked)->toBe(2);

    Livewire::test(ViewBooking::class, ['record' => $this->booking->getRouteKey()])
        ->callAction('mark_paid', data: ['provider_ref' => 'FP-123'])
        ->assertNotified('Ваучер отправлен туристу');

    expect($this->booking->fresh()->status)->toBe(BookingStatus::VoucherSent)
        ->and($this->booking->payments()->first()->provider_ref)->toBe('FP-123');
    Mail::assertQueued(BookingMail::class, fn ($m) => $m->type === 'voucher');
});

it('shows a notification instead of an error when seats ran out', function () {
    $this->departure->update(['seats_booked' => 7]);

    Livewire::test(ViewBooking::class, ['record' => $this->booking->getRouteKey()])
        ->callAction('confirm', data: ['payment_link_url' => 'https://pay.example/abc'])
        ->assertNotified('Нельзя выполнить');

    expect($this->booking->fresh()->status)->toBe(BookingStatus::New);
});

it('requires a payment link to confirm', function () {
    Livewire::test(ViewBooking::class, ['record' => $this->booking->getRouteKey()])
        ->callAction('confirm', data: ['payment_link_url' => ''])
        ->assertHasFormErrors(['payment_link_url' => 'required']);
});

it('writes who changed the status into the history', function () {
    Livewire::test(ViewBooking::class, ['record' => $this->booking->getRouteKey()])->callAction('start_checking');

    $event = $this->booking->events()->reorder()->latest('id')->first();
    expect($event->to_status)->toBe(BookingStatus::Checking)
        ->and($event->user_id)->toBe(auth()->id());
});
