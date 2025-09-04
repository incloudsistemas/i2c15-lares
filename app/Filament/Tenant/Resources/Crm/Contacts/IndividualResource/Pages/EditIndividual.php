<?php

namespace App\Filament\Tenant\Resources\Crm\Contacts\IndividualResource\Pages;

use App\Filament\Tenant\Resources\Crm\Contacts\IndividualResource;
use App\Models\Crm\Contacts\Individual;
use App\Services\Crm\Contacts\ContactService;
use App\Services\Polymorphics\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIndividual extends EditRecord
{
    protected static string $resource = IndividualResource::class;

    protected array $oldRecord;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    fn(ContactService $service, Actions\DeleteAction $action, Individual $record) =>
                    $service->preventDeleteIf(action: $action, contact: $record->contact)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $contact = $this->record->contact;

        $data['contact']['roles'] = $contact->roles->pluck('id')
            ->toArray();

        $data['contact']['name'] = $contact->name;
        $data['contact']['email'] = $contact->email;
        $data['contact']['additional_emails'] = $contact->additional_emails;
        $data['contact']['phones'] = $contact->phones;
        $data['contact']['source_id'] = $contact->source_id;
        $data['contact']['user_id'] = $contact->user_id;
        $data['contact']['status'] = $contact->status->value;
        $data['contact']['complement'] = $contact->complement;

        $data['legal_entities'] = $this->record->legalEntities->pluck('id')
            ->toArray();

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->record->load([
            'contact',
            'contact.owner:id,name',
            'contact.roles:id,name',
            'contact.source:id,name',
            'addresses',
            'legalEntities'
        ]);

        $this->oldRecord = $this->record->replicate()
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->updateContact();
        $this->syncRoles();
        $this->syncLegalEntities();

        $this->logActivity();
    }

    protected function updateContact(): void
    {
        $this->data['contact']['additional_emails'] = array_values($this->data['contact']['additional_emails']);
        $this->data['contact']['phones'] = array_values($this->data['contact']['phones']);

        $this->record->contact->update($this->data['contact']);
    }

    protected function syncRoles(): void
    {
        $this->record->contact->roles()
            ->sync($this->data['contact']['roles']);
    }

    protected function syncLegalEntities(): void
    {
        $this->record->legalEntities()
            ->sync($this->data['legal_entities']);
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
        $logService->logUpdatedActivity(
            currentRecord: $this->record,
            oldRecord: $this->oldRecord,
            description: "Contato <b>{$this->record->contact->name}</b> atualizado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
