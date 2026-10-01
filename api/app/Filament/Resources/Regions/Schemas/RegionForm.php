<?php

namespace App\Filament\Resources\Regions\Schemas;

use App\Filament\Forms\Fields;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Название (EN)')->required()->placeholder('Naryn & Song-Kul'),
                Fields::slug(),
            ]),
            Textarea::make('summary')->label('Описание для страницы региона (EN)')->rows(3),
            TagsInput::make('places')->label('Места (EN)')->placeholder('Song-Kul'),
            SpatieMediaLibraryFileUpload::make('hero')->label('Обложка')->collection('hero')->image()->maxSize(15 * 1024),
            Section::make('SEO')->collapsed()->columns(2)->schema([
                TextInput::make('meta_title')->label('Title (EN)')->maxLength(60),
                TextInput::make('meta_description')->label('Description (EN)')->maxLength(155),
            ]),
        ]);
    }
}
