<?php

namespace App\Policies\System;

use App\Models\System\TenantPlan;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class TenantPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(permission: 'Visualizar Planos');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TenantPlan $tenantPlan): bool
    {
        return $user->hasPermissionTo(permission: 'Visualizar Planos');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(permission: 'Cadastrar Planos');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TenantPlan $tenantPlan): bool
    {
        return $user->hasPermissionTo(permission: 'Editar Planos');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TenantPlan $tenantPlan): bool
    {
        return $user->hasPermissionTo(permission: 'Deletar Planos');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TenantPlan $tenantPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TenantPlan $tenantPlan): bool
    {
        return false;
    }
}
