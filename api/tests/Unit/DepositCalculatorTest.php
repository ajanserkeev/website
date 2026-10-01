<?php

use App\Services\Booking\DepositCalculator;

it('rounds the deposit to whole dollars', function (int $total, float $rate, int $deposit) {
    expect((new DepositCalculator)->depositCents($total, $rate))->toBe($deposit);
})->with([
    '1 × $330 at 15% = $49.50 → $50' => [33000, 15, 5000],
    '2 × $330 at 15% = $99' => [66000, 15, 9900],
    '$42 at 18% = $7.56 → $8' => [4200, 18, 800],
    '$890 at 12% = $106.80 → $107' => [89000, 12, 10700],
    '$10 at 14% = $1.40 → $1' => [1000, 14, 100],
]);
