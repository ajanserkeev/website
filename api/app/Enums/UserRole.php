<?php

namespace App\Enums;

/** Admin users: admin manages everything, manager handles bookings, content edits tours and guides. */
enum UserRole: string
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
}
