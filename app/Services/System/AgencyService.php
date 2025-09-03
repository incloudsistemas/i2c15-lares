<?php

namespace App\Services\System;

use App\Models\System\Agency;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AgencyService extends BaseService
{
    public function __construct(protected Agency $agency)
    {
        parent::__construct();
    }

    public function getQueryByAgencies(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]) // 1 - Ativo
            ->orderBy('name', 'asc');
    }

    public function getOptionsByAgencies(): array
    {
        return $this->agency->byStatuses(statuses: [1]) // 1 - Ativo
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Agency $agency): void
    {
        $title = __('Ação proibida: Exclusão de agência');

        if ($this->isAssignedToTeams(agency: $agency)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Esta agência possui equipes associadas. Para excluir, você deve primeiro desvincular todas as equipes que estão associados a ela.'))
                ->send();

            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $agency) {
            if ($this->isAssignedToTeams(agency: $agency)) {
                $blocked[] = $agency->name;
                continue;
            }

            $allowed[] = $agency;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('As seguintes agências não podem ser excluídas: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Algumas agências não puderam ser excluídas'))
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

    protected function isAssignedToTeams(Agency $agency): bool
    {
        return $agency->teams()
            ->exists();
    }
}
