<?php

namespace App\Filament\Resources\Places;

use App\Filament\Resources\Places\Pages\CreatePlace;
use App\Filament\Resources\Places\Pages\EditPlace;
use App\Filament\Resources\Places\Pages\ListPlaces;
use App\Filament\Resources\Places\Schemas\PlaceForm;
use App\Filament\Resources\Places\Tables\PlacesTable;
use App\Models\Place;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Places of interest on the site's maps; tour days point to them. */
class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'место';

    protected static ?string $pluralModelLabel = 'Места на карте';

    protected static ?string $recordTitleAttribute = 'name';

    /** Filament would title-case every word ("Места На Карте"). */
    public static function getTitleCasePluralModelLabel(): string
    {
        return static::$pluralModelLabel;
    }

    public static function form(Schema $schema): Schema
    {
        return PlaceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlacesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlaces::route('/'),
            'create' => CreatePlace::route('/create'),
            'edit' => EditPlace::route('/{record}/edit'),
        ];
    }
}
