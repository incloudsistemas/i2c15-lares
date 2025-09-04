<?php

namespace App\Filament\Tenant\Resources\Crm\Contacts\IndividualResource\Pages;

use App\Filament\Tenant\Resources\Crm\Contacts\IndividualResource;
use App\Services\Polymorphics\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIndividual extends CreateRecord
{
    protected static string $resource = IndividualResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->createContact();
        $this->attachRoles();
        $this->attachLegalEntities();

        $this->logActivity();
    }

    protected function createContact(): void
    {
        $this->data['contact']['additional_emails'] = array_values($this->data['contact']['additional_emails']);
        $this->data['contact']['phones'] = array_values($this->data['contact']['phones']);

        $this->record->contact()
            ->create($this->data['contact']);
    }

    protected function attachRoles(): void
    {
        $this->record->contact->roles()
            ->attach($this->data['contact']['roles']);
    }

    protected function attachLegalEntities(): void
    {
        $this->record->legalEntities()
            ->attach($this->data['legal_entities']);
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'contact',
            'contact.owner:id,name',
            'contact.roles:id,name',
            'contact.source:id,name',
            'addresses',
            'legalEntities'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logCreatedActivity(
            currentRecord: $this->record,
            description: "Novo contato <b>{$this->record->contact->name}</b> cadastrado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
