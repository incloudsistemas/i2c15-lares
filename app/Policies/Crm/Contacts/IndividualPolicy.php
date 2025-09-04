<?php

namespace App\Policies\Crm\Contacts;

use App\Models\Crm\Contacts\Individual;
use App\Models\System\User;
use App\Services\Crm\Contacts\ContactService;
use Illuminate\Auth\Access\Response;

class IndividualPolicy
{
    public function __construct(protected ContactService $service)
    {
        //
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo(permission: 'Visualizar [CRM] Contatos');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Individual $individual)
    {
        if (!$user->hasPermissionTo(permission: 'Visualizar [CRM] Contatos')) {
            return false;
        }

        return $this->service->checkOwnerAccess(user: $user, contact: $individual->contact);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo(permission: 'Cadastrar [CRM] Contatos');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Individual $individual)
    {
        if (!$user->hasPermissionTo(permission: 'Editar [CRM] Contatos')) {
            return false;
        }

        return $this->service->checkOwnerAccess(user: $user, contact: $individual->contact);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Individual $individual)
    {
        if (!$user->hasPermissionTo(permission: 'Deletar [CRM] Contatos')) {
            return false;
        }

        return $this->service->checkOwnerAccess(user: $user, contact: $individual->contact);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Individual $individual): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Individual $individual): bool
    {
        return false;
    }
}
