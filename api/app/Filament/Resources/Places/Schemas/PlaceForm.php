<?php

namespace App\Filament\Resources\Places\Schemas;

use App\Enums\PlaceKind;
use App\Filament\Forms\Components\LocationPicker;
use App\Filament\Forms\Fields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(3)->schema([
                TextInput::make('name')->label('Название (EN)')->required()->maxLength(120)->placeholder('Song-Kul Lake'),
                Fields::slug(),
                Select::make('kind')->label('Тип')->options(PlaceKind::class)->default(PlaceKind::Viewpoint)->required(),
                Select::make('region_id')->label('Регион')->relationship('region', 'name')->preload(),
                Toggle::make('is_featured')->label('Крупно на карте')->helperText('Главные места видны с первого взгляда.')->inline(false),
                Toggle::make('is_published')->label('Показывать на сайте')->default(true)->inline(false),
            ]),
            Textarea::make('summary')->label('Описание (EN)')->rows(3)->maxLength(400)
                ->helperText('1–2 предложения для всплывающей карточки на карте.'),
            Section::make('Где находится')->columns(3)->schema([
                LocationPicker::make('location')->hiddenLabel()->columnSpanFull(),
                TextInput::make('latitude')->label('Широта')->numeric()->required()->minValue(39)->maxValue(43.5)->step(0.000001),
                TextInput::make('longitude')->label('Долгота')->numeric()->required()->minValue(69)->maxValue(81)->step(0.000001),
                TextInput::make('altitude_m')->label('Высота, м')->numeric()->minValue(0)->maxValue(7500),
            ]),
            SpatieMediaLibraryFileUpload::make('photo')->label('Фото')->collection('photo')->image()->maxSize(15 * 1024),
        ]);
    }
}
