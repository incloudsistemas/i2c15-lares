<?php

namespace App\Filament\Resources\System\TenantPlanResource\Pages;

use App\Filament\Resources\System\TenantPlanResource;
use App\Models\System\TenantPlan;
use App\Services\Polymorphics\ActivityLogService;
use App\Services\System\TenantPlanService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenantPlan extends EditRecord
{
    protected static string $resource = TenantPlanResource::class;

    protected array $oldRecord;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    fn(TenantPlanService $service, Actions\DeleteAction $action, TenantPlan $record) =>
                    $service->preventDeleteIf(action: $action, tenantPlan: $record)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $this->oldRecord = $this->record->replicate()
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->logActivity();
    }

    protected function logActivity(): void
    {
        $logService = app()->make(ActivityLogService::class);
        $logService->logUpdatedActivity(
            currentRecord: $this->record,
            oldRecord: $this->oldRecord,
            description: "Plano <b>{$this->record->name}</b> atualizado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
