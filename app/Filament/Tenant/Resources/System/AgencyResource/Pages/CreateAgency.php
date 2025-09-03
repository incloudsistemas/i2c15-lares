<?php

namespace App\Filament\Tenant\Resources\System\AgencyResource\Pages;

use App\Filament\Tenant\Resources\System\AgencyResource;
use App\Services\Polymorphics\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAgency extends CreateRecord
{
    protected static string $resource = AgencyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->attachLeaderUsers();

        $this->logActivity();
    }

    protected function attachLeaderUsers(): void
    {
        $this->record->users()
            ->attach($this->data['users']);
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'users:id,name',
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logCreatedActivity(
            currentRecord: $this->record,
            description: "Nova agência <b>{$this->record->name}</b> cadastrada por <b>" . auth()->user()->name . "</b>"
        );
    }
}
