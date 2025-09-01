<?php

namespace App\Filament\Resources\System\UserResource\Pages;

use App\Filament\Resources\System\UserResource;
use App\Models\System\User;
use App\Services\Polymorphics\ActivityLogService;
use App\Services\System\UserService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected array $oldRecord;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(
                    fn(UserService $service, Actions\DeleteAction $action, User $record) =>
                    $service->preventDeleteIf(action: $action, user: $record)
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['email_confirmation'] = $data['email'];

        $data['address']['zipcode'] = $this->record->address?->zipcode;
        $data['address']['uf'] = $this->record->address?->uf?->name;
        $data['address']['city'] = $this->record->address?->city;
        $data['address']['district'] = $this->record->address?->district;
        $data['address']['address_line'] = $this->record->address?->address_line;
        $data['address']['number'] = $this->record->address?->number;
        $data['address']['complement'] = $this->record->address?->complement;
        $data['address']['reference'] = $this->record->address?->reference;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password']) && !empty(trim($data['password']))) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['password_confirmation']);

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->record->load([
            'roles:id,name',
            'address'
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
            'roles:id,name',
            'address'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logUpdatedActivity(
            currentRecord: $this->record,
            oldRecord: $this->oldRecord,
            description: "Usuário <b>{$this->record->name}</b> atualizado por <b>" . auth()->user()->name . "</b>"
        );
    }
}
