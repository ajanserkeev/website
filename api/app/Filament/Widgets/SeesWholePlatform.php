<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;

/** Sales widgets in /admin: every operator, for the super admin and managers. */
trait SeesWholePlatform
{
    protected function operatorId(): ?int
    {
        return null;
    }

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
