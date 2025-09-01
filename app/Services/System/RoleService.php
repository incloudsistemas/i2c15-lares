<?php

namespace App\Services\System;

use App\Models\System\Role;
use App\Models\System\User;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RoleService extends BaseService
{
    public function __construct(protected Role $role)
    {
        parent::__construct();
    }

    public static function getArrayOfRolesToAvoidByAuthUserRoles(User $user): array
    {
        $userRoles = $user->roles->pluck('id')
            ->toArray();

        // avoid role 2 = client/customer, ALWAYS.
        // avoid role 1 = superadmin, if auth user role isn't superadmin.
        // avoid role 3 = admin, if auth user role isn't superadmin or admin.

        // 1 - Superadmin
        if (in_array(1, $userRoles)) {
            return [2];
        }

        // 3 - Admin
        if (in_array(3, $userRoles)) {
            return [1, 2];
        }

        // Other roles
        return [1, 2, 3];
    }

    public function getQueryByAuthUserRoles(Builder $query): Builder
    {
        $user = auth()->user();
        $rolesToAvoid = static::getArrayOfRolesToAvoidByAuthUserRoles(user: $user);

        return $query->whereNotIn('id', $rolesToAvoid)
            ->orderBy('id', 'asc');
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Role $role): void
    {
        $title = __('Ação proibida: Exclusão de nível de acesso');

        if ($this->isAssignedToUsers(role: $role)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Este nível de acesso possui usuários associados. Para excluir, você deve primeiro desvincular todos os usuários que estão associados a ele.'))
                ->send();

            $action->halt();
        }

        if ($this->isFixedRole(role: $role)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Este nível de acesso não pode ser excluído do sistema por questões de segurança.'))
                ->send();

            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $role) {
            if (
                $this->isAssignedToUsers(role: $role) ||
                $this->isFixedRole(role: $role)
            ) {
                $blocked[] = $role->name;
                continue;
            }

            $allowed[] = $role;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('Os seguintes níveis de acessos não podem ser excluídos: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Alguns níveis de acessos não puderam ser excluídos'))
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

    protected function isAssignedToUsers(Role $role): bool
    {
        return $role->users()
            ->exists();
    }

    protected function isFixedRole(Role $role): bool
    {
        return in_array($role->name, ['Superadministrador', 'Cliente', 'Administrador']);
    }
}
