<?php

namespace App\Filament\Resources\Views\Pages;

use App\Filament\Resources\Views\ViewResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewView extends ViewRecord
{
    protected static string $resource = ViewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
