<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Who a user is. Staff (admin, manager, content) work in /admin, partners see their operator's numbers
 * in /partner, tourists sign in on the site with Google.
 */
enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Content = 'content';
    case Partner = 'partner';
    case Tourist = 'tourist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Super admin',
            self::Manager => 'Manager',
            self::Content => 'Content editor',
            self::Partner => 'Partner',
            self::Tourist => 'Traveler',
        };
    }

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Супер-админ',
            self::Manager => 'Менеджер заявок',
            self::Content => 'Контент',
            self::Partner => 'Партнёр (турфирма)',
            self::Tourist => 'Турист',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Manager, self::Content => 'primary',
            self::Partner => 'warning',
            self::Tourist => 'gray',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Admin, self::Manager, self::Content], true);
    }

    /** @return list<self> */
    public static function staff(): array
    {
        return [self::Admin, self::Manager, self::Content];
    }
}
