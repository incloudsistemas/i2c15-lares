<?php

namespace App\Filament\Resources\System\TenantCategoryResource\Pages;

use App\Filament\Resources\System\TenantCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTenantCategories extends ManageRecords
{
    protected static string $resource = TenantCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
