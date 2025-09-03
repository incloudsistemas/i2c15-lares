<?php

namespace App\Filament\Tenant\Resources\System\UserResource\Pages;

use App\Filament\Tenant\Resources\System\UserResource;
use App\Services\Polymorphics\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Hash::make($data['password']);

        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->attachTeams();
        $this->createAddress();

        $this->logActivity();
    }

    protected function attachTeams(): void
    {
        // Attach coordinators
        if (!empty($this->data['teams']['coordinators'])) {
            $data = collect($this->data['teams']['coordinators'])
                ->mapWithKeys(function ($id) {
                    return [$id => ['role' => 1]]; // 1 = Coordenador
                })
                ->all();

            $this->record->coordinatorTeams()
                ->attach($data);
        }

        // Attach collaborators
        if (!empty($this->data['teams']['collaborators'])) {
            $data = collect($this->data['teams']['collaborators'])
                ->mapWithKeys(function ($id) {
                    return [$id => ['role' => 2]]; // 2 = Colaborador
                })
                ->all();

            $this->record->collaboratorTeams()
                ->attach($data);
        }
    }

    protected function createAddress(): void
    {
        $this->data['address']['is_main'] = true;

        $this->record->address()
            ->create($this->data['address']);
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'roles:id,name',
            'coordinatorTeams:id,name',
            'collaboratorTeams:id,name',
            'address'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logCreatedActivity(
            currentRecord: $this->record,
            description: "Novo usuário <b>{$this->record->name}</b> cadastrado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
