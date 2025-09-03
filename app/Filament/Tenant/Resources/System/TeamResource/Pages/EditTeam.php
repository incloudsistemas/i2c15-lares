<?php

namespace App\Filament\Tenant\Resources\System\TeamResource\Pages;

use App\Filament\Tenant\Resources\System\TeamResource;
use App\Models\System\Team;
use App\Services\Polymorphics\ActivityLogService;
use App\Services\System\TeamService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected array $oldRecord;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    fn(TeamService $service, Actions\DeleteAction $action, Team $record) =>
                    $service->preventDeleteIf(action: $action, team: $record)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['coordinators'] = $this->record->coordinators()
            ->pluck('id')
            ->toArray();

        $data['collaborators'] = $this->record->collaborators()
            ->pluck('id')
            ->toArray();

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->record->load([
            'agency:id,name',
            'coordinators:id,name',
            'collaborators:id,name'
        ]);

        $this->oldRecord = $this->record->replicate()
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->syncCoordinatorUsers();
        $this->syncCollaboratorUsers();
    }

    protected function syncCoordinatorUsers(): void
    {
        $data = collect($this->data['coordinators'])
            ->mapWithKeys(fn($id) => [$id => ['role' => 1]]) // 1 = Coordenador
            ->all();

        $this->record->coordinators()
            ->sync($data);
    }

    protected function syncCollaboratorUsers(): void
    {
        $data = collect($this->data['collaborators'])
            ->mapWithKeys(fn($id) => [$id => ['role' => 2]]) // 2 = Colaborador
            ->all();

        $this->record->collaborators()
            ->sync($data);
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'agency:id,name',
            'coordinators:id,name',
            'collaborators:id,name'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logUpdatedActivity(
            currentRecord: $this->record,
            oldRecord: $this->oldRecord,
            description: "Equipe <b>{$this->record->name}</b> atualizada por <b>" . auth()->user()->name . "</b>"
        );
    }
}
