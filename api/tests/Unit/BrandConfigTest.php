<?php

use Tests\TestCase;

uses(TestCase::class);

it('uses Tunduk Trips branding and the TT booking prefix by default', function () {
    expect(config('brand.name'))->toBe('Tunduk Trips')
        ->and(config('brand.booking_prefix'))->toBe('TT')
        ->and(config('brand.timezone'))->toBe('Asia/Bishkek');
});
