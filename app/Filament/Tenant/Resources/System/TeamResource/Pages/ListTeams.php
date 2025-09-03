<?php

namespace App\Filament\Tenant\Resources\System\TeamResource\Pages;

use App\Filament\Tenant\Resources\System\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
