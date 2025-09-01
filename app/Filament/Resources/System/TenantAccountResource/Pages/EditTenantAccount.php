<?php

namespace App\Filament\Resources\System\TenantAccountResource\Pages;

use App\Filament\Resources\System\TenantAccountResource;
use App\Models\System\TenantAccount;
use App\Models\System\User;
use App\Services\Polymorphics\ActivityLogService;
use App\Services\System\TenantAccountService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditTenantAccount extends EditRecord
{
    protected static string $resource = TenantAccountResource::class;

    protected array $oldRecord;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    fn(TenantAccountService $service, Actions\DeleteAction $action, TenantAccount $record) =>
                    $service->preventDeleteIf(action: $action, tenantAccount: $record)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['address']['zipcode'] = $this->record->address?->zipcode;
        $data['address']['uf'] = $this->record->address?->uf?->name;
        $data['address']['city'] = $this->record->address?->city;
        $data['address']['district'] = $this->record->address?->district;
        $data['address']['address_line'] = $this->record->address?->address_line;
        $data['address']['number'] = $this->record->address?->number;
        $data['address']['complement'] = $this->record->address?->complement;
        $data['address']['reference'] = $this->record->address?->reference;
        $data['address']['gmap_coordinates'] = $this->record->address?->gmap_coordinates;

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->record->load([
            'tenant:id',
            'address',
            'plan:id,name',
            'categories:id,name'
        ]);

        $this->oldRecord = $this->record->replicate()
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->updateAddress();

        $this->logActivity();
    }

    protected function updateAddress(): void
    {
        $this->data['address']['is_main'] = true;

        $this->record->address()
            ->updateOrCreate(
                [
                    'addressable_type' => MorphMapByClass(model: get_class($this->record)),
                    'addressable_id'   => $this->record->id
                ],
                $this->data['address']
            );
    }

    protected function logActivity(): void
    {
        $this->record->load([
            'tenant:id',
            'address',
            'plan:id,name',
            'categories:id,name'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logUpdatedActivity(
            currentRecord: $this->record,
            oldRecord: $this->oldRecord,
            description: "Conta de cliente <b>{$this->record->name}</b> atualizada por <b>" . auth()->user()->name . "</b>"
        );
    }
}
