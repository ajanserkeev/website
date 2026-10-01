<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Admin users: admin manages everything, manager handles bookings, content edits tours and guides. */
enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Content = 'content';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Content => 'Content editor',
        };
    }

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Manager => 'Менеджер заявок',
            self::Content => 'Контент',
        };
    }
}
