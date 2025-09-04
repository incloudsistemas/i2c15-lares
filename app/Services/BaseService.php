<?php

namespace App\Services;

use App\Models\System\Team;
use App\Models\System\TenantAccount;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class BaseService
{
    protected ?TenantAccount $currentTenantAccount = null;

    public function __construct()
    {
        $this->currentTenantAccount = tenant()?->account;
    }

    protected function getErrorException(\Throwable $e): array
    {
        // Check the class of the exception to handle it appropriately
        $message = match (get_class($e)) {
            ValidationException::class => $e->errors(),
            default                    => $e->getMessage(),
        };

        return [
            'success' => false,
            'message' => $message,
        ];
    }

    public function tableSearchByStatus(
        Builder $query,
        string $search,
        string $enumClass = DefaultStatusEnum::class,
        string $columnName = 'status',
    ): Builder {
        if (!class_exists($enumClass) || !method_exists($enumClass, 'getAssociativeArray')) {
            return $query;
        }

        $statuses = $enumClass::getAssociativeArray();

        $matchingStatuses = [];
        foreach ($statuses as $index => $status) {
            if (stripos($status, $search) !== false) {
                $matchingStatuses[] = $index;
            }
        }

        if ($matchingStatuses) {
            return $query->whereIn($columnName, $matchingStatuses);
        }

        return $query;
    }

    public function tableSortByStatus(
        Builder $query,
        string $direction,
        string $enumClass = DefaultStatusEnum::class,
        string $columnName = 'status',
    ): Builder {
        if (!class_exists($enumClass) || !method_exists($enumClass, 'getAssociativeArray')) {
            return $query;
        }

        $statuses = $enumClass::getAssociativeArray();

        $caseParts = [];
        $bindings = [];

        foreach ($statuses as $key => $status) {
            $caseParts[] = "WHEN ? THEN ?";
            $bindings[] = $key;
            $bindings[] = $status;
        }

        $orderByCase = "CASE {$columnName} " . implode(' ', $caseParts) . " END";

        return $query->orderByRaw("$orderByCase $direction", $bindings);
    }

    public function tableFilterByCreatedAt(Builder $query, array $data): Builder
    {
        if (!$data['created_from'] && !$data['created_until']) {
            return $query;
        }

        return $query
            ->when(
                $data['created_from'],
                function (Builder $query, $date) use ($data) {
                    if (empty($data['created_until'])) {
                        return $query->whereDate('created_at', '=', $date);
                    }

                    return $query->whereDate('created_at', '>=', $date);
                }
            )
            ->when(
                $data['created_until'],
                fn(Builder $query, $date): Builder =>
                $query->whereDate('created_at', '<=', $date)
            );
    }

    public function tableFilterIndicateUsingByCreatedAt(array $data): ?string
    {
        return $this->indicateUsingByDates(
            from: $data['created_from'],
            until: $data['created_until'],
            display: 'Cadastro'
        );
    }

    public function tableFilterByUpdatedAt(Builder $query, array $data): Builder
    {
        if (!$data['updated_from'] && !$data['updated_until']) {
            return $query;
        }

        return $query->when(
            $data['updated_from'],
            function (Builder $query, $date) use ($data) {
                if (empty($data['updated_until'])) {
                    return $query->whereDate('updated_at', '=', $date);
                }

                return $query->whereDate('updated_at', '>=', $date);
            }
        )
            ->when(
                $data['updated_until'],
                fn(Builder $query, $date): Builder =>
                $query->whereDate('updated_at', '<=', $date),
            );
    }

    public function tableFilterIndicateUsingByUpdatedAt(array $data): ?string
    {
        return $this->indicateUsingByDates(
            from: $data['updated_from'],
            until: $data['updated_until'],
            display: 'Atualização'
        );
    }

    public function indicateUsingByDates(?string $from, ?string $until, string $display): ?string
    {
        if (blank($from) && blank($until)) {
            return null;
        }

        $displayFrom  = !blank($from) ? ConvertEnToPtBrDate(date: $from) : null;
        $displayUntil = !blank($until) ? ConvertEnToPtBrDate(date: $until) : null;

        $parts = [];
        if ($from && $until) {
            if ($from === $until) {
                $parts[] = __("{$display} em: :date", ['date' => $displayFrom]);
            } else {
                $parts[] = __("{$display} entre: :from e :until", [
                    'from'  => $displayFrom,
                    'until' => $displayUntil
                ]);
            }
        } elseif ($from) {
            $parts[] = __("{$display} de: :date", ['date' => $displayFrom]);
        } elseif ($until) {
            $parts[] = __("{$display} até: :date", ['date' => $displayUntil]);
        }

        return implode(' | ', $parts);
    }

    public function getOwnedUsersByAuthUserRolesAgenciesAndTeams(User $user): array
    {
        $ids = [$user->id];

        if ($user->hasAnyRole(['Líder', 'Coordenador'])) {
            // Users from teams in which is a coordinator (via pivot)
            $coordinatorTeamIds = $user->coordinatorTeams()
                ->toBase()
                ->pluck('teams.id');

            $ids = array_merge(
                $ids,
                DB::table('team_user')
                    ->whereIn('team_id', $coordinatorTeamIds)
                    ->pluck('user_id')
                    ->all()
            );

            // If Leader: all teams from your agencies
            if ($user->hasRole('Líder')) {
                $agencyTeamIds = $user->agencies()
                    ->join('teams', 'agencies.id', '=', 'teams.agency_id')
                    ->toBase()
                    ->pluck('teams.id');

                $ids = array_merge(
                    $ids,
                    DB::table('team_user')
                        ->whereIn('team_id', $agencyTeamIds)
                        ->pluck('user_id')
                        ->all()
                );
            }
        }

        // Normalize: int + unique
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return $ids;
    }
}
