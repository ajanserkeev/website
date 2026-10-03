<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

/**
 * Map for choosing a point: fills the latitude, longitude and (when empty) altitude fields next to it.
 * The field itself stores nothing.
 */
class LocationPicker extends Field
{
    protected string $view = 'filament.forms.components.location-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    /** State path of a sibling field, like "data.latitude". */
    public function getSiblingStatePath(string $name): string
    {
        return Str::beforeLast($this->getStatePath(), '.').'.'.$name;
    }
}
