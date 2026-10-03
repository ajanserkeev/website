<?php

namespace App\Filament\Resources\Tours\Schemas;

use App\Enums\DepartureStatus;
use App\Enums\TourItemKind;
use App\Enums\TourStatus;
use App\Enums\TourType;
use App\Filament\Forms\Components\RouteBuilder;
use App\Filament\Forms\Fields;
use App\Models\Operator;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Tour editor (step 4.2): Main, Itinerary, Route map, Included, Photos, Group dates, Private prices, SEO.
 * Texts are in English because they go to the site; labels are in Russian for the team.
 */
class TourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                self::main(),
                self::itinerary(),
                self::routeMap(),
                self::included(),
                self::photos(),
                self::groupDates(),
                self::privatePrices(),
                self::seo(),
            ]),
        ]);
    }

    private static function main(): Tab
    {
        return Tab::make('Основное')->schema([
            Grid::make(3)->schema([
                TextInput::make('title')->label('Название (EN)')->required()->maxLength(120)->columnSpan(2),
                Select::make('status')->label('Статус')->options(TourStatus::class)->default(TourStatus::Draft)->required(),
                Fields::slug()->columnSpan(2),
                Select::make('type')->label('Тип')->options(TourType::class)->default(TourType::MultiDay)->required(),
                Select::make('operator_id')->label('Турфирма')->relationship('operator', 'name')->searchable()->preload()->required()->live(),
                TextInput::make('commission_rate')->label('Комиссия для этого тура, %')->numeric()->minValue(0)->maxValue(50)->suffix('%')
                    ->placeholder(fn (Get $get) => ($rate = Operator::find($get('operator_id'))?->commission_rate) ? "как у фирмы: {$rate}%" : 'как у фирмы')
                    ->helperText('Пусто: берётся комиссия фирмы.'),
                TextInput::make('sort_weight')->label('Вес в сортировке')->numeric()->default(0)
                    ->helperText('Больше = выше в «Recommended».'),
            ]),
            Textarea::make('summary')->label('Короткое описание (EN)')->required()->rows(2)->maxLength(300)
                ->helperText('2 предложения для карточки и поиска.'),
            Textarea::make('description')->label('Описание (EN)')->required()->rows(6)
                ->helperText('Абзацы через пустую строку.'),
            TagsInput::make('highlights')->label('Главное о туре (EN)')->placeholder('Suitable for first-time riders'),
            Section::make('Регионы и активности')->columns(2)->schema([
                Select::make('regions')->label('Регионы')->relationship('regions', 'name')->multiple()->preload()->required(),
                Select::make('activities')->label('Активности')->relationship('activities', 'name')->multiple()->preload()->required(),
            ]),
            Section::make('Факты')->columns(4)->schema([
                TextInput::make('duration_days')->label('Дней')->numeric()->minValue(1)->required(),
                Select::make('difficulty')->label('Сложность')->required()->options([
                    1 => '1 · Easy', 2 => '2 · Easy', 3 => '3 · Moderate', 4 => '4 · Challenging', 5 => '5 · Very challenging',
                ]),
                TextInput::make('difficulty_note')->label('Пояснение к сложности (EN)')->placeholder('4–6 h riding a day')->columnSpan(2),
                TextInput::make('group_size_min')->label('Группа от')->numeric()->minValue(1)->default(1)->required(),
                TextInput::make('group_size_max')->label('Группа до')->numeric()->minValue(1)->required()
                    ->gte('group_size_min'),
                TextInput::make('min_age')->label('Возраст от')->numeric()->minValue(0),
                TextInput::make('max_altitude_m')->label('Макс. высота, м')->numeric()->minValue(0),
                Fields::month('season_from')->label('Сезон с')->required(),
                Fields::month('season_to')->label('Сезон по')->required(),
                TagsInput::make('guide_languages')->label('Языки гида (EN)')->placeholder('English')->required()->columnSpan(2),
                TextInput::make('route')->label('Маршрут (EN)')->placeholder('Kyzart → Song-Kul → Kochkor')->columnSpan(2),
                TextInput::make('start_point')->label('Старт (EN)')->columnSpan(1),
                TextInput::make('end_point')->label('Финиш (EN)')->columnSpan(1),
            ]),
        ]);
    }

    private static function itinerary(): Tab
    {
        return Tab::make('Программа')->schema([
            Repeater::make('days')->label('Дни')->relationship()->minItems(1)
                // The item position is the day number: drag to reorder days.
                ->orderColumn('day_number')
                ->itemLabel(fn (array $state) => $state['title'] ?? null)
                ->collapsible()
                ->addActionLabel('Добавить день')
                ->schema([
                    TextInput::make('title')->label('Заголовок дня (EN)')->required()->placeholder('Kyzart village → Kilemche jailoo'),
                    Textarea::make('description')->label('Описание (EN)')->required()->rows(3),
                    Grid::make(4)->schema([
                        TextInput::make('overnight')->label('Ночёвка (EN)')->placeholder('Yurt camp'),
                        TextInput::make('activity_hours')->label('Часы в пути (EN)')->placeholder('4 h riding'),
                        TextInput::make('max_altitude_m')->label('Макс. высота, м')->numeric(),
                        Select::make('place_id')->label('Место дня на карте')->relationship('place', 'name')->searchable()->preload()
                            ->helperText('Где заканчивается день. Нет в списке: «Места на карте».'),
                    ]),
                    CheckboxList::make('meals')->label('Питание')->columns(3)->options([
                        'breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин',
                    ]),
                ]),
        ]);
    }

    private static function routeMap(): Tab
    {
        return Tab::make('Маршрут')->schema([
            RouteBuilder::make('route_geojson')->hiddenLabel(),
        ]);
    }

    private static function included(): Tab
    {
        $list = fn (string $name, string $label, TourItemKind $kind) => Repeater::make($name)->label($label)
            ->relationship()
            ->orderColumn('sort')
            ->defaultItems(0)
            ->simple(TextInput::make('text')->required())
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => [...$data, 'kind' => $kind]);

        return Tab::make('Включено')->schema([
            Grid::make(2)->schema([
                $list('includedItems', 'Включено (EN)', TourItemKind::Included),
                $list('excludedItems', 'Не включено (EN)', TourItemKind::Excluded),
            ]),
            Repeater::make('faqs')->label('Вопросы и ответы (EN)')->relationship()->orderColumn('sort')->defaultItems(0)
                ->itemLabel(fn (array $state) => $state['question'] ?? null)
                ->collapsible()
                ->addActionLabel('Добавить вопрос')
                ->schema([
                    TextInput::make('question')->label('Вопрос')->required(),
                    Textarea::make('answer')->label('Ответ')->required()->rows(3),
                ]),
        ]);
    }

    private static function photos(): Tab
    {
        return Tab::make('Фото')->schema([
            SpatieMediaLibraryFileUpload::make('gallery')->label('Фото тура')
                ->collection('gallery')
                ->multiple()
                ->reorderable()
                ->image()
                ->maxFiles(30)
                ->maxSize(15 * 1024)
                ->panelLayout('grid')
                ->helperText('15–30 фото от 2000 px, без логотипов и водяных знаков. Первое фото — обложка. Подписи (alt) заполняются во вкладке «Подписи к фото» ниже.'),
        ]);
    }

    private static function groupDates(): Tab
    {
        return Tab::make('Даты заездов')->schema([
            Repeater::make('departures')->label('Групповые заезды')->relationship()->defaultItems(0)
                ->itemLabel(fn (array $state) => ($state['starts_on'] ?? null) ? "{$state['starts_on']} – {$state['ends_on']}" : null)
                ->collapsible()
                ->addActionLabel('Добавить заезд')
                ->columns(4)
                ->schema([
                    DatePicker::make('starts_on')->label('Начало')->required()->live(),
                    DatePicker::make('ends_on')->label('Конец')->required()->afterOrEqual('starts_on'),
                    Fields::money('price_cents')->label('Цена за человека')->required(),
                    Fields::money('child_price_cents')->label('Цена для ребёнка')->helperText('Пусто: как взрослый.'),
                    TextInput::make('seats_total')->label('Мест всего')->numeric()->minValue(1)->required(),
                    TextInput::make('seats_booked')->label('Занято')->numeric()->minValue(0)->default(0)
                        ->helperText('Меняется автоматически при бронях.'),
                    Select::make('status')->label('Статус')->options(DepartureStatus::class)->default(DepartureStatus::Open)->required(),
                ]),
        ]);
    }

    private static function privatePrices(): Tab
    {
        return Tab::make('Индивидуально')->schema([
            Repeater::make('privatePrices')->label('Цены для индивидуального тура в любую дату')->relationship()->defaultItems(0)
                ->itemLabel(fn (array $state) => isset($state['group_size_from'], $state['group_size_to']) ? "{$state['group_size_from']}–{$state['group_size_to']} чел." : null)
                ->addActionLabel('Добавить диапазон')
                ->columns(4)
                ->schema([
                    TextInput::make('group_size_from')->label('Человек от')->numeric()->minValue(1)->required(),
                    TextInput::make('group_size_to')->label('до')->numeric()->minValue(1)->required()->gte('group_size_from'),
                    Fields::money('price_per_person_cents')->label('Цена за человека')->required(),
                    Fields::money('child_price_cents')->label('Цена для ребёнка'),
                ]),
        ]);
    }

    private static function seo(): Tab
    {
        return Tab::make('SEO')->schema([
            TextInput::make('meta_title')->label('Title (EN)')->maxLength(60)
                ->helperText('До 60 символов. Пусто: «{Tour}: {N}-Day {Activity} Tour in Kyrgyzstan».'),
            Textarea::make('meta_description')->label('Description (EN)')->maxLength(155)->rows(2)
                ->helperText('До 155 символов. Пусто: собирается из длительности, региона и цены.'),
            DateTimePicker::make('published_at')->label('Опубликован')->native(false),
        ]);
    }
}
