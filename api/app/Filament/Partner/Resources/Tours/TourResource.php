<?php

namespace App\Filament\Partner\Resources\Tours;

use App\Filament\Partner\Resources\Tours\Pages\ListTours;
use App\Filament\Partner\Resources\Tours\Pages\ViewTour;
use App\Filament\Partner\Resources\Tours\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\Tours\TourResource as AdminTourResource;
use App\Models\Departure;
use App\Models\Tour;
use App\Services\Partner\PartnerStats;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** The partner's tours with their sales; the content itself is edited by the platform team. */
class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'тур';

    protected static ?string $pluralModelLabel = 'Мои туры';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('operator_id', auth()->user()->operator_id ?? -1);
    }

    /** Filament would title-case every word ("Мои Туры"). */
    public static function getTitleCasePluralModelLabel(): string
    {
        return static::$pluralModelLabel;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $sold = fn () => fn (Builder $q) => $q->whereIn('status', PartnerStats::SOLD);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['bookings as sold_count' => $sold()])
                ->withSum(['bookings as operator_sum' => $sold()], 'balance_cents')
                ->with('upcomingDepartures'))
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->label('Тур')->weight('bold')->searchable()->wrap(),
                TextColumn::make('status')->label('На сайте')->badge(),
                TextColumn::make('duration_days')->label('Дней'),
                TextColumn::make('next_departure')->label('Ближайший заезд')
                    ->state(fn (Tour $r) => ($d = $r->upcomingDepartures->first())
                        ? $d->starts_on->format('d.m.Y')." · занято {$d->seats_booked} из {$d->seats_total}" : null)
                    ->placeholder('нет дат'),
                TextColumn::make('sold_count')->label('Броней')->sortable(),
                TextColumn::make('operator_sum')->label('Вам, всего')->sortable()
                    ->formatStateUsing(fn ($state) => '$'.number_format(((int) $state) / 100))->color('success'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('site')->label('На сайте')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Tour $r) => AdminTourResource::siteUrl($r), shouldOpenInNewTab: true),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (int $cents) => '$'.number_format($cents / 100);
        $totals = fn (Tour $r) => PartnerStats::forTour($r->operator_id, $r->id)->totals();

        return $schema->columns(1)->components([
            Section::make('Итоги по туру за всё время')->schema([
                Grid::make(6)->schema([
                    TextEntry::make('sold')->label('Броней с предоплатой')->state(fn (Tour $r) => $totals($r)['bookings']),
                    TextEntry::make('travelers')->label('Туристов')->state(fn (Tour $r) => $totals($r)['travelers']),
                    TextEntry::make('total')->label('Сумма туров')->state(fn (Tour $r) => $money($totals($r)['total_cents'])),
                    TextEntry::make('commission')->label('Комиссия площадки')->color('warning')
                        ->state(fn (Tour $r) => $money($totals($r)['commission_cents'])),
                    TextEntry::make('net')->label('Вам')->color('success')->weight('bold')
                        ->state(fn (Tour $r) => $money($totals($r)['operator_cents'])),
                    TextEntry::make('waiting')->label('Ждут оплаты')->state(fn (Tour $r) => $totals($r)['waiting']),
                ]),
            ]),
            Section::make('Ближайшие заезды')->schema([
                RepeatableEntry::make('upcomingDepartures')->hiddenLabel()->placeholder('Групповых дат нет')->grid(3)->schema([
                    TextEntry::make('starts_on')->hiddenLabel()->weight('bold')
                        ->formatStateUsing(fn ($state, Departure $record) => $record->starts_on->format('d.m').' – '.$record->ends_on->format('d.m.Y')),
                    TextEntry::make('seats_booked')->hiddenLabel()
                        ->formatStateUsing(fn ($state, Departure $record) => "Занято {$record->seats_booked} из {$record->seats_total} · \$".number_format($record->price_cents / 100).' за чел.'),
                ]),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [BookingsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTours::route('/'),
            'view' => ViewTour::route('/{record}'),
        ];
    }
}
