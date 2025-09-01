<?php

namespace App\Services\System;

use App\Enums\ProfileInfos\UfEnum;
use App\Models\System\TenantAccount;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class TenantAccountService extends BaseService
{
    public function __construct(protected TenantAccount $tenantAccount)
    {
        parent::__construct();
    }

    public function tableSearchByNameAndCpfCnpj(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): Builder {
            return $query->where('cpf_cnpj', 'like', "%{$search}%")
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '') LIKE ?", ["%{$search}%"])
                ->orWhere('name', 'like', "%{$search}%");
        });
    }

    public function tableSearchByCityAndState(Builder $query, string $search): Builder
    {
        $ufs = UfEnum::getAssociativeArray();

        $matchingUfs = [];
        foreach ($ufs as $index => $uf) {
            if (stripos($uf, $search) !== false) {
                $matchingUfs[] = $index;
            }
        }

        return $query->whereHas('address', function (Builder $query) use ($search, $matchingUfs): Builder {
            $query->where('city', 'like', "%{$search}%");

            if ($matchingUfs) {
                $query->orWhereIn('uf', $matchingUfs);
            }

            return $query;
        });
    }

    public function tableFilterByUfs(Builder $query, array $data): Builder
    {
        if (!$data['values'] || empty($data['values'])) {
            return $query;
        }

        return $query->whereHas('address', function (Builder $query) use ($data): Builder {
            return $query->whereIn('uf', $data['values']);
        });
    }

    public function getQueryByTenantAccounts(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]) // 1 - Ativo
            ->orderBy('name', 'asc');
    }

    public function getOptionsByTenantAccounts(): array
    {
        return $this->tenantAccount->byStatuses(statuses: [1]) // 1 - Ativo
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, TenantAccount $tenantAccount): void
    {
        // $title = __('Ação proibida: Exclusão de nível de acesso');
    }
}
