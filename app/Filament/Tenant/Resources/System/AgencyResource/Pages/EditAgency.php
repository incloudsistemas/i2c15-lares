<?php

namespace App\Filament\Tenant\Resources\System\AgencyResource\Pages;

use App\Filament\Tenant\Resources\System\AgencyResource;
use App\Models\System\Agency;
use App\Services\Polymorphics\ActivityLogService;
use App\Services\System\AgencyService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgency extends EditRecord
{
    protected static string $resource = AgencyResource::class;

    protected array $oldRecord;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    fn(AgencyService $service, Actions\DeleteAction $action, Agency $record) =>
                    $service->preventDeleteIf(action: $action, agency: $record)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['users'] = $this->record->users()
            ->pluck('id')
            ->toArray();

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->record->load([
            'users:id,name',
        ]);

        $this->oldRecord = $this->record->replicate()
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->syncLeaderUsers();

        $this->logActivity();
    }

    protected function syncLeaderUsers(): void
    {
        $this->record->users()
            ->sync($this->data['users']);
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'users:id,name',
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logUpdatedActivity(
            currentRecord: $this->record,
            oldRecord: $this->oldRecord,
            description: "Agência <b>{$this->record->name}</b> atualizada por <b>" . auth()->user()->name . "</b>"
        );
    }
}
