<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

/** Reusable admin fields. */
final class Fields
{
    /** Price typed in dollars ($330), stored in integer cents (33000). */
    public static function money(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->prefix('$')
            ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100));
    }

    public static function month(string $name): Select
    {
        return Select::make($name)->options(
            collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => Str::ucfirst(now()->setMonth($m)->locale('ru')->translatedFormat('F'))])->all()
        );
    }

    /** Slug field: generated from the title when empty, editable before publishing. */
    public static function slug(): TextInput
    {
        return TextInput::make('slug')
            ->label('Адрес (slug)')
            ->helperText('Часть ссылки на сайте. Заполнится из названия, если оставить пустым. После публикации не меняйте.')
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }
}
