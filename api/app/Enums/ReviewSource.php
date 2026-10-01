<?php

namespace App\Enums;

/** Own reviews may appear in JSON-LD; imported ones may not (Google rules). */
enum ReviewSource: string
{
    case Site = 'site';
    case Imported = 'imported';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Site',
            self::Imported => 'Imported',
        };
    }
}
