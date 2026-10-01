<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Forms\Fields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/** Travel guide editor. Sections mirror the guide page: heading, paragraphs, optional bullet list. */
class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(3)->schema([
                TextInput::make('title')->label('Заголовок (EN)')->required()->columnSpan(2),
                DateTimePicker::make('published_at')->label('Опубликован')->native(false)
                    ->helperText('Пусто: черновик, на сайте не виден.'),
                Fields::slug()->columnSpan(2),
                TextInput::make('reading_minutes')->label('Минут чтения')->numeric()->minValue(1)->default(5),
            ]),
            Textarea::make('excerpt')->label('Лид (EN)')->required()->rows(3)
                ->helperText('Первый абзац под заголовком и описание в списке гайдов.'),
            SpatieMediaLibraryFileUpload::make('hero')->label('Обложка')->collection('hero')->image()->maxSize(15 * 1024),
            Repeater::make('facts')->label('Короткие факты (EN)')->columns(2)->defaultItems(0)
                ->schema([
                    TextInput::make('label')->label('Что')->placeholder('Altitude')->required(),
                    TextInput::make('value')->label('Значение')->placeholder('3,016 m')->required(),
                ]),
            Repeater::make('sections')->label('Разделы (EN)')->required()->minItems(1)
                ->itemLabel(fn (array $state) => $state['title'] ?? null)
                ->collapsible()
                ->addActionLabel('Добавить раздел')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('title')->label('Заголовок раздела')->required()->columnSpan(2)->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => blank($get('id')) ? $set('id', Str::slug((string) $state)) : null),
                        TextInput::make('id')->label('Якорь')->required()->alphaDash()
                            ->helperText('Для оглавления: #when-to-go'),
                    ]),
                    Repeater::make('paragraphs')->label('Абзацы')->simple(Textarea::make('text')->rows(3)->required())->minItems(1)
                        ->addActionLabel('Добавить абзац'),
                    Repeater::make('bullets')->label('Список (необязательно)')->simple(TextInput::make('text')->required())->defaultItems(0)
                        ->addActionLabel('Добавить пункт'),
                ]),
            Section::make('SEO')->collapsed()->columns(2)->schema([
                TextInput::make('meta_title')->label('Title (EN)')->maxLength(60),
                TextInput::make('meta_description')->label('Description (EN)')->maxLength(155),
            ]),
        ]);
    }
}
