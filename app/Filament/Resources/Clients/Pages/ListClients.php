<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected string $view = 'filament.resources.clients.list-clients';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
