<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

/** Display-only rates for the currency switcher; payments are always charged in USD. */
#[Guarded(['id'])]
class CurrencyRate extends Model
{
    protected function casts(): array
    {
        return [
            'rate_per_usd' => 'decimal:6',
            'fetched_at' => 'datetime',
        ];
    }
}
