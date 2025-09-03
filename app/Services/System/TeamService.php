<?php

namespace App\Services\System;

use App\Models\System\Team;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TeamService extends BaseService
{
    public function __construct(protected Team $team)
    {
        parent::__construct();
    }

    public function getQueryByTeams(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]) // 1 - Ativo
            ->orderBy('name', 'asc');
    }

    public function getOptionsByTeams(): array
    {
        return $this->team->byStatuses(statuses: [1]) // 1 - Ativo
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getOptionsByTeamsGroupedByAgencies(): array
    {
        $withAgencies = $this->team->with('agency')
            ->byStatuses(statuses: [1]) // 1 - Ativo
            ->whereHas('agency', function (Builder $query): Builder {
                return $query->where('status', 1); // 1 - Ativo
            })
            ->orderBy('name', 'asc')
            ->get()
            ->groupBy('agency.name')
            ->map(function ($teams) {
                return $teams->pluck('name', 'id');
            })
            ->toArray();

        $withoutAgencies = $this->team->byStatuses(statuses: [1]) // 1 - Ativo
            ->whereDoesntHave('agency')
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();

        if (!empty($withoutAgencies)) {
            $withAgencies[__('Sem Agência')] = $withoutAgencies;
        }

        ksort($withAgencies, SORT_NATURAL | SORT_FLAG_CASE);

        return $withAgencies;
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Team $team): void
    {
        $title = __('Ação proibida: Exclusão de equipe');

        // if ($this->isAssignedToAgencies(team: $team)) {
        //     Notification::make()
        //         ->title($title)
        //         ->warning()
        //         ->body(__('Esta equipe possui uma agência associada. Para excluir, você deve primeiro desvincular a agência que está associada a ela.'))
        //         ->send();

        //     $action->halt();
        // }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $team) {
            // if ($this->isAssignedToAgencies(team: $team)) {
            //     $blocked[] = $team->name;
            //     continue;
            // }

            $allowed[] = $team;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('As seguintes equipes não podem ser excluídas: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Algumas equipes não puderam ser excluídas'))
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
}
