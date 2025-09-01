<?php

namespace App\Services\Polymorphics;

use App\Models\Polymorphics\Address;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class AddressService extends BaseService
{
    public function __construct(protected Address $address, protected ActivityLogService $logService)
    {
        parent::__construct();
    }

    public function setUniqueMainAddress(array $data, ?Address $address, Model $ownerRecord): void
    {
        if ($data['is_main']) {
            $ownerRecord->addresses()
                ->where('is_main', true)
                ->when($address, function ($query) use ($address) {
                    return $query->where('id', '<>', $address->id);
                })
                ->update(['is_main' => false]);
        }
    }

    public function getAddressByZipcodeViaCep(?string $zipcode): array
    {
        if (!$zipcode) {
            return ["error" => "CEP não fornecido. Por favor, preencha o CEP."];
        }

        $zipcode = preg_replace('/\D/', '', $zipcode);

        if (strlen($zipcode) !== 8) {
            return ["error" => "CEP inválido. O CEP deve conter exatamente 8 números."];
        }

        try {
            $response = Http::get("https://viacep.com.br/ws/{$zipcode}/json/");

            if ($response->failed()) {
                return ["error" => "Erro ao consultar a API do ViaCEP. Por favor, tente novamente."];
            }

            $address = $response->json();

            if (isset($address['erro'])) {
                return ["error" => "CEP não encontrado. Por favor, verifique o CEP informado."];
            }

            return $address;
        } catch (\Exception $e) {
            return ["error" => "Falha ao buscar endereço. Por favor, tente novamente mais tarde."];
        }
    }

    public function getAddressByZipcodeBrasilApi(?string $zipcode): array
    {
        if (!$zipcode) {
            return ["error" => "CEP não fornecido. Por favor, preencha o CEP."];
        }

        $zipcode = preg_replace('/\D/', '', $zipcode);

        if (strlen($zipcode) !== 8) {
            return ["error" => "CEP inválido. O CEP deve conter exatamente 8 números."];
        }

        try {
            $response = Http::get("https://brasilapi.com.br/api/cep/v2/{$zipcode}");

            if ($response->failed()) {
                return ["error" => "CEP não encontrado ou API indisponível. Por favor, verifique o CEP informado."];
            }

            $address = $response->json();

            if (isset($address['message'])) {
                return ["error" => $address['message']];
            }

            return $address;
        } catch (\Exception $e) {
            return ["error" => "Falha ao buscar endereço. Por favor, tente novamente mais tarde."];
        }
    }

    public function tableDefaultSort(Builder $query): Builder
    {
        return $query->orderBy('is_main', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function afterCreateAction(Model $ownerRecord, Address $address, array $data): void
    {
        $this->logService->logOwnerRecordRelationCreatedActivity(
            ownerRecord: $ownerRecord,
            currentRecord: $address,
            description: $this->getActivityLogDescription(event: 'created')
        );
    }

    public function mutateFormDataToEdit(Model $ownerRecord, Address $address, array $data): array
    {
        $data['_old_record'] = $address->replicate()
            ->toArray();

        return $data;
    }

    public function afterEditAction(Model $ownerRecord, Address $address, array $data): void
    {
        $this->logService->logOwnerRecordRelationUpdatedActivity(
            ownerRecord: $ownerRecord,
            currentRecord: $address,
            oldRecord: $data['_old_record'],
            description: $this->getActivityLogDescription(event: 'updated')
        );
    }


    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Address $address, Model $ownerRecord): void
    {
        $title = __('Ação proibida: Exclusão de endereço');

        if ($this->isMainAddressDeletable(address: $address, ownerRecord: $ownerRecord)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Não é possível excluir o endereço principal porque há outros endereços cadastrados. Para excluir este endereço, você deve primeiro definir outro endereço como principal.'))
                ->send();

            $action->halt();
        }
    }

    public function afterDeleteAction(Model $ownerRecord, Address $address): void
    {
        $this->logService->logOwnerRecordRelationDeletedActivity(
            ownerRecord: $ownerRecord,
            oldRecord: $address,
            description: $this->getActivityLogDescription(event: 'deleted')
        );
    }

    public function deleteBulkAction(Collection $records, Model $ownerRecord): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $address) {
            if ($this->isMainAddressDeletable(address: $address, ownerRecord: $ownerRecord)) {
                $blocked[] = $address->zipcode;
                continue;
            }

            $allowed[] = $address;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('Os seguintes endereços não podem ser excluídos: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Alguns endereços não puderam ser excluídos'))
                ->warning()
                ->body($message)
                ->send();
        }

        collect($allowed)->each->delete();

        if (!empty($allowed)) {
            Notification::make()
                ->title(__('Excluído'))
                ->success()
                ->send();
        }
    }

    protected function isMainAddressDeletable(Address $address, Model $ownerRecord): bool
    {
        return $address->is_main && $ownerRecord->addresses()->count() > 1;
    }

    public function afterDeleteBulkAction(Model $ownerRecord, Collection $records): void
    {
        foreach ($records as $address) {
            $this->logService->logOwnerRecordRelationDeletedActivity(
                ownerRecord: $ownerRecord,
                oldRecord: $address,
                description: $this->getActivityLogDescription(event: 'deleted')
            );
        }
    }

    protected function getActivityLogDescription(string $event): string
    {
        $user = auth()->user();

        return match ($event) {
            'updated' => "Endereço atualizado por <b>{$user->name}</b>",
            'deleted' => "Endereço excluído por <b>{$user->name}</b>",
            default   => "Novo endereço cadastrado por <b>{$user->name}</b>",
        };
    }
}
