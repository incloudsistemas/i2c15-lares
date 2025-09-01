<?php

namespace App\Services\System;

use App\Models\System\Permission;
use App\Services\BaseService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class PermissionService extends BaseService
{
    public function __construct(protected Permission $permission)
    {
        parent::__construct();
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Permission $permission): void
    {
        $title = __('Ação proibida: Exclusão de permissão');

        if ($this->isAssignedToRoles(permission: $permission)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Esta permissão possui níveis de acessos associados. Para excluir, você deve primeiro desvincular todos os níveis de acessos que estão associados a ela.'))
                ->send();

            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $permission) {
            if ($this->isAssignedToRoles(permission: $permission)) {
                $blocked[] = $permission->name;
                continue;
            }

            $allowed[] = $permission;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('As seguintes permissões não podem ser excluídas: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Algumas permissões não puderam ser excluídas'))
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

    protected function isAssignedToRoles(Permission $permission): bool
    {
        return $permission->roles()
            ->exists();
    }
}
