<?php

namespace App\Filament\Resources\System\TenantPlanResource\Pages;

use App\Filament\Resources\System\TenantPlanResource;
use App\Services\Polymorphics\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantPlan extends CreateRecord
{
    protected static string $resource = TenantPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->logActivity();
    }

    protected function logActivity(): void
    {
        $logService = app()->make(ActivityLogService::class);
        $logService->logCreatedActivity(
            currentRecord: $this->record,
            description: "Novo plano <b>{$this->record->name}</b> cadastrado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
