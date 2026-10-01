<?php

use App\Enums\DepartureStatus;
use App\Enums\TourStatus;
use App\Enums\TourType;
use App\Enums\UserRole;
use App\Filament\Resources\Operators\Pages\CreateOperator;
use App\Filament\Resources\Operators\Pages\EditOperator;
use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Filament\Resources\Tours\Pages\EditTour;
use App\Filament\Resources\Users\UserResource;
use App\Models\Activity;
use App\Models\Collection;
use App\Models\Operator;
use App\Models\Post;
use App\Models\Region;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
});

it('opens every admin list page', function (string $path) {
    $this->seed(DemoCatalogSeeder::class);

    $this->get("/admin/{$path}")->assertOk();
})->with(['tours', 'operators', 'regions', 'activities', 'collections', 'posts', 'users']);

it('opens the edit pages of seeded records', function () {
    $this->seed(DemoCatalogSeeder::class);
    $tour = Tour::where('slug', 'song-kul-horse-trek-yurt-stay')->firstOrFail();

    $this->get("/admin/tours/{$tour->id}/edit")->assertOk()->assertSee('Song-Kul Horse Trek');
    $this->get("/admin/operators/{$tour->operator_id}/edit")->assertOk();
    $this->get('/admin/posts/'.Post::firstOrFail()->id.'/edit')->assertOk();
    $this->get('/admin/collections/'.Collection::firstOrFail()->id.'/edit')->assertOk();
});

it('creates an operator with guides', function () {
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateOperator::class)
        ->fillForm([
            'name' => 'Naryn Riders',
            'commission_rate' => 14,
            'is_active' => true,
            'whatsapp' => '+996 555 000 111',
            'guides' => [['name' => 'Bakyt', 'languages' => ['English'], 'note' => 'Horseman']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $operator = Operator::where('name', 'Naryn Riders')->firstOrFail();
    expect($operator->slug)->toBe('naryn-riders')
        ->and((float) $operator->commission_rate)->toBe(14.0)
        ->and($operator->guides()->pluck('name')->all())->toBe(['Bakyt']);

    $undoRepeaterFake();
});

it('creates a tour with days and a departure priced in dollars', function () {
    $undoRepeaterFake = Repeater::fake();
    $operator = Operator::factory()->create();
    $region = Region::factory()->create();
    $activity = Activity::factory()->create();

    Livewire::test(CreateTour::class)
        ->fillForm([
            'title' => 'Kel-Suu Horse Trek',
            'status' => TourStatus::Draft,
            'type' => TourType::MultiDay,
            'operator_id' => $operator->id,
            'summary' => 'Two days to a hidden lake.',
            'description' => 'Ride to Kel-Suu.',
            'regions' => [$region->id],
            'activities' => [$activity->id],
            'duration_days' => 2,
            'difficulty' => 3,
            'group_size_min' => 2,
            'group_size_max' => 8,
            'season_from' => 7,
            'season_to' => 9,
            'guide_languages' => ['English'],
            'days' => [
                ['title' => 'To the yurt camp', 'description' => 'Drive and ride.', 'meals' => ['lunch', 'dinner']],
                ['title' => 'To the lake', 'description' => 'Walk to the shore.', 'meals' => ['breakfast']],
            ],
            'departures' => [
                ['starts_on' => '2027-07-10', 'ends_on' => '2027-07-11', 'price_cents' => '330', 'seats_total' => 8, 'seats_booked' => 0, 'status' => DepartureStatus::Open],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tour = Tour::where('slug', 'kel-suu-horse-trek')->firstOrFail();
    expect($tour->days->pluck('day_number')->all())->toBe([1, 2])
        ->and($tour->departures->first()->price_cents)->toBe(33000)
        ->and($tour->regions->pluck('id')->all())->toBe([$region->id]);

    $undoRepeaterFake();
});

it('shows prices in dollars and keeps cents when an operator edits a tour', function () {
    $this->seed(DemoCatalogSeeder::class);
    $tour = Tour::where('slug', 'song-kul-horse-trek-yurt-stay')->firstOrFail();

    Livewire::test(EditTour::class, ['record' => $tour->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tour->departures()->min('price_cents'))->toBe(33000)
        ->and($tour->days()->count())->toBe(3);
});

it('keeps an operator slug that was typed by hand', function () {
    $operator = Operator::factory()->create(['slug' => 'custom-slug']);

    Livewire::test(EditOperator::class, ['record' => $operator->getRouteKey()])
        ->fillForm(['name' => 'Renamed Operator'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($operator->fresh()->slug)->toBe('custom-slug');
});

it('hides user management from non-admins', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Manager]));

    expect(UserResource::canViewAny())->toBeFalse();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/tours')->assertOk();
});
