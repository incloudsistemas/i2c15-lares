<?php

namespace App\Services\Crm\Contacts;

use App\Enums\DefaultStatusEnum;
use App\Models\Crm\Contacts\Role;
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

    public function getQueryByRoles(Builder $query): Builder
    {
        return $query->byStatuses(statuses: [1]); // 1 - Ativo
    }

    public function getOptionsByRoles(): array
    {
        return $this->role->byStatuses(statuses: [1]) // 1 - Ativo
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * $action can be:
     * Filament\Tables\Actions\DeleteAction;
     * Filament\Actions\DeleteAction;
     */
    public function preventDeleteIf($action, Role $role): void
    {
        $title = __('Ação proibida: Exclusão de tipo de contato');

        if ($this->isAssignedToContacts(role: $role)) {
            Notification::make()
                ->title($title)
                ->warning()
                ->body(__('Este tipo possui contatos associados. Para excluir, você deve primeiro desvincular todos os contatos que estão associados a ele.'))
                ->send();

            $action->halt();
        }
    }

    public function deleteBulkAction(Collection $records): void
    {
        $blocked = [];
        $allowed = [];

        foreach ($records as $role) {
            if ($this->isAssignedToContacts(role: $role)) {
                $blocked[] = $role->name;
                continue;
            }

            $allowed[] = $role;
        }

        if (!empty($blocked)) {
            $displayBlocked = array_slice($blocked, 0, 5);
            $extraCount = count($blocked) - 5;

            $message = __('Os seguintes tipos de contatos não podem ser excluídos: ') . implode(', ', $displayBlocked);

            if ($extraCount > 0) {
                $message .= " ... (+$extraCount " . __('outros') . ")";
            }

            Notification::make()
                ->title(__('Alguns tipos de contatos não puderam ser excluídos'))
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

    protected function isAssignedToContacts(Role $role): bool
    {
        return $role->contacts()
            ->exists();
    }
}
