<?php

namespace App\Policies\System;

use App\Models\System\TenantCategory;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class TenantCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(permission: 'Visualizar Categorias de Contas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TenantCategory $tenantCategory): bool
    {
        return $user->hasPermissionTo(permission: 'Visualizar Categorias de Contas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(permission: 'Cadastrar Categorias de Contas');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TenantCategory $tenantCategory): bool
    {
        return $user->hasPermissionTo(permission: 'Editar Categorias de Contas');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TenantCategory $tenantCategory): bool
    {
        return $user->hasPermissionTo(permission: 'Deletar Categorias de Contas');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TenantCategory $tenantCategory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TenantCategory $tenantCategory): bool
    {
        return false;
    }
}
