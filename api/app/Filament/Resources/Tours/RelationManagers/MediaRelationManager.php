<?php

namespace App\Filament\Resources\Tours\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Alt text for each tour photo (step 4.4). Upload and order photos in the "Фото" tab;
 * here the team writes a short English description of what is in the picture.
 */
class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Подписи к фото';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('collection_name', 'gallery')->orderBy('order_column'))
            ->paginated(false)
            ->columns([
                ImageColumn::make('preview')->label('')
                    ->state(fn (Media $record) => $record->hasGeneratedConversion('w480') ? $record->getUrl('w480') : $record->getUrl())
                    ->imageHeight(64),
                TextColumn::make('order_column')->label('№'),
                TextInputColumn::make('alt')->label('Подпись (alt, EN)')
                    ->placeholder('What is in the photo, e.g. "Yurts on the shore of Song-Kul at sunset"')
                    ->state(fn (Media $record) => $record->getCustomProperty('alt'))
                    ->rules(['nullable', 'max:160'])
                    ->updateStateUsing(function (Media $record, ?string $state) {
                        $record->setCustomProperty('alt', $state)->save();

                        return $state;
                    }),
                TextColumn::make('size')->label('Размер')->formatStateUsing(fn (int $state) => round($state / 1024 / 1024, 1).' МБ'),
            ]);
    }
}
