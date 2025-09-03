<?php

namespace App\Filament\Tenant\Resources\System\AgencyResource\Pages;

use App\Filament\Tenant\Resources\System\AgencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgencies extends ListRecords
{
    protected static string $resource = AgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
