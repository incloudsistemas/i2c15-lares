<?php

namespace App\Policies\System;

use App\Models\System\TenantAccount;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class TenantAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(permission: 'Visualizar Contas de Clientes');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TenantAccount $tenantAccount): bool
    {
        return $user->hasPermissionTo(permission: 'Visualizar Contas de Clientes');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(permission: 'Cadastrar Contas de Clientes');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TenantAccount $tenantAccount): bool
    {
        if ($user->id === $tenantAccount->user_id) {
            return true;
        }

        return $user->hasPermissionTo(permission: 'Editar Contas de Clientes');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TenantAccount $tenantAccount): bool
    {
        return $user->hasPermissionTo(permission: 'Deletar Contas de Clientes');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TenantAccount $tenantAccount): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TenantAccount $tenantAccount): bool
    {
        return false;
    }
}
