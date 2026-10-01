<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Forms\Fields;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Название (EN)')->required()->placeholder('Horse riding'),
                Fields::slug(),
            ]),
            Textarea::make('summary')->label('Описание для страницы активности (EN)')->rows(3),
            SpatieMediaLibraryFileUpload::make('hero')->label('Обложка')->collection('hero')->image()->maxSize(15 * 1024),
            Section::make('SEO')->collapsed()->columns(2)->schema([
                TextInput::make('meta_title')->label('Title (EN)')->maxLength(60),
                TextInput::make('meta_description')->label('Description (EN)')->maxLength(155),
            ]),
        ]);
    }
}
