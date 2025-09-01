<?php

namespace App\Services\System;

use App\Models\System\TenantPlan;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TenantPlanService extends BaseService
{
    public function __construct(protected TenantPlan $tenantPlan)
    {
        parent::__construct();
    }

    public function getQueryByPlans(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]); // 1 - Ativo
    }

    public function getOptionsByPlans(): array
    {
        return $this->tenantPlan->byStatuses(statuses: [1]) // 1 - Ativo
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, TenantPlan $tenantPlan): void
    {
        $title = __('Ação proibida: Exclusão de plano');

        if ($this->isAssignedToTenantAccounts(tenantPlan: $tenantPlan)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Este plano possui contas associadas. Para excluir, você deve primeiro desvincular todas as contas que estão associadas a ele.'))
                ->send();

            // $action->cancel();
            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $tenantPlan) {
            if ($this->isAssignedToTenantAccounts(tenantPlan: $tenantPlan)) {
                $blocked[] = $tenantPlan->name;
                continue;
            }

            $allowed[] = $tenantPlan;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('Os seguintes planos não podem ser excluídos: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Alguns planos não puderam ser excluídos'))
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

    protected function isAssignedToTenantAccounts(TenantPlan $tenantPlan): bool
    {
        return $tenantPlan->tenantAccounts()
            ->exists();
    }
}
