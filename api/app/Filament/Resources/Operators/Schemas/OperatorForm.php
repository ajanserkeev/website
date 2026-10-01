<?php

namespace App\Filament\Resources\Operators\Schemas;

use App\Filament\Forms\Fields;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OperatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Основное')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Название')->required()->maxLength(255),
                        Fields::slug(),
                        TextInput::make('base_city')->label('Город')->maxLength(255),
                        TextInput::make('founded_year')->label('Год основания')->numeric()->minValue(1990)->maxValue(now()->year),
                    ]),
                    Textarea::make('description')->label('Описание для сайта (EN)')->rows(4),
                    SpatieMediaLibraryFileUpload::make('logo')->label('Логотип')->collection('logo')->image()->imageEditor(),
                    Toggle::make('is_active')->label('Активна')->default(true)
                        ->helperText('Туры неактивной фирмы не показываются на сайте.'),
                ]),
                Tab::make('Договор')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('commission_rate')->label('Комиссия, %')->required()->numeric()->minValue(0)->maxValue(50)->suffix('%')
                            ->helperText('Её турист платит как предоплату. Можно переопределить в отдельном туре.'),
                        DatePicker::make('contract_signed_at')->label('Договор подписан'),
                    ]),
                    Textarea::make('notes')->label('Заметки (только для команды)')->rows(3),
                ]),
                Tab::make('Контакты')->schema([
                    Section::make()->description('Туристу эти контакты видны только в ваучере после оплаты предоплаты. На сайте и в API их нет.')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('contact_name')->label('Контактное лицо'),
                                TextInput::make('whatsapp')->label('WhatsApp для заявок')->tel(),
                                TextInput::make('phone')->label('Телефон')->tel(),
                                TextInput::make('email')->label('Email')->email(),
                            ]),
                        ]),
                ]),
                Tab::make('Рейтинги')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('tripadvisor_url')->label('TripAdvisor, ссылка')->url()->columnSpan(1),
                        TextInput::make('tripadvisor_rating')->label('Оценка')->numeric()->minValue(1)->maxValue(5)->step(0.1),
                        TextInput::make('tripadvisor_reviews')->label('Отзывов')->numeric()->minValue(0),
                        TextInput::make('google_url')->label('Google, ссылка')->url(),
                        TextInput::make('google_rating')->label('Оценка')->numeric()->minValue(1)->maxValue(5)->step(0.1),
                        TextInput::make('google_reviews')->label('Отзывов')->numeric()->minValue(0),
                    ]),
                ]),
                Tab::make('Гиды')->schema([
                    Repeater::make('guides')->label('Гиды')->relationship()->orderColumn('sort')->defaultItems(0)
                        ->itemLabel(fn (array $state) => $state['name'] ?? null)
                        ->collapsible()
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')->label('Имя')->required(),
                                TagsInput::make('languages')->label('Языки (EN)')->placeholder('English')->required(),
                            ]),
                            TextInput::make('note')->label('Коротко о гиде (EN)')->placeholder('12 seasons guiding in the Tian Shan'),
                        ]),
                ]),
            ]),
        ]);
    }
}
