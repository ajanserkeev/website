<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Reviews: travelers write them from their account after a completed trip and they go live at once
 * (verified booking); the team hides abusive ones here.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'отзыв';

    protected static ?string $pluralModelLabel = 'Отзывы';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['tour', 'booking']))
            ->columns([
                TextColumn::make('rating')->label('Оценка')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')->sortable(),
                TextColumn::make('tour.title')->label('Тур')->limit(30)->searchable(),
                TextColumn::make('author_name')->label('Автор')->searchable()->description(fn (Review $r) => $r->country),
                TextColumn::make('body')->label('Текст')->limit(80)->wrap()->tooltip(fn (Review $r) => $r->body),
                IconColumn::make('booking_id')->label('Бронь')->boolean()->state(fn (Review $r) => $r->isVerifiedBooking())
                    ->tooltip(fn (Review $r) => $r->booking?->code),
                ToggleColumn::make('is_published')->label('На сайте')
                    ->afterStateUpdated(fn (Review $r, bool $state) => $r->update(['published_at' => $state ? ($r->published_at ?? now()) : $r->published_at])),
                TextColumn::make('created_at')->label('Создан')->date('d.m.Y')->sortable(),
            ])
            ->filters([TernaryFilter::make('is_published')->label('На сайте')])
            ->recordActions([DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReviews::route('/')];
    }
}
