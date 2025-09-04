<?php

namespace App\Filament\Tenant\Resources\Crm\Contacts\LegalEntityResource\Pages;

use App\Filament\Tenant\Resources\Crm\Contacts\LegalEntityResource;
use App\Services\Polymorphics\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLegalEntity extends CreateRecord
{
    protected static string $resource = LegalEntityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->createContact();
        $this->attachRoles();
        $this->attachIndividuals();

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

    protected function attachIndividuals(): void
    {
        $this->record->individuals()
            ->attach($this->data['individuals']);
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'contact',
            'contact.owner:id,name',
            'contact.roles:id,name',
            'contact.source:id,name',
            'addresses',
            'individuals'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logCreatedActivity(
            currentRecord: $this->record,
            description: "Novo contato <b>{$this->record->contact->name}</b> cadastrado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
