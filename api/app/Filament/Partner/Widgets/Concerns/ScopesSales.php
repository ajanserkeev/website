<?php

namespace App\Filament\Partner\Widgets\Concerns;

use App\Services\Partner\PartnerStats;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Sales widgets read the dashboard filters and are limited to the signed-in partner's operator.
 * The super admin's copies (App\Filament\Widgets) override operatorId() to see the whole platform.
 */
trait ScopesSales
{
    use InteractsWithPageFilters;

    protected function operatorId(): ?int
    {
        // Never null for a partner: canAccessPanel() requires an operator.
        return auth()->user()->operator_id ?? -1;
    }

    protected function stats(): PartnerStats
    {
        return PartnerStats::fromFilters($this->operatorId(), $this->pageFilters);
    }

    protected static function money(int $cents): string
    {
        return '$'.number_format($cents / 100);
    }
}
