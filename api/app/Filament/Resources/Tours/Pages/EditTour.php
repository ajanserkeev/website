<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\Pages\Concerns\BuildsTourRoute;
use App\Filament\Resources\Tours\TourResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTour extends EditRecord
{
    use BuildsTourRoute;

    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_on_site')->label('Открыть на сайте')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => TourResource::siteUrl($this->getRecord()), shouldOpenInNewTab: true),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
