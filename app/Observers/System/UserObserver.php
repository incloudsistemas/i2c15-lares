<?php

namespace App\Observers\System;

use App\Models\System\User;
use App\Services\Polymorphics\ActivityLogService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    public function deleted(User $user): void
    {
        $user->load([
            'roles:id,name',
            'address'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $user,
            description: "Usuário <b>{$user->name}</b> excluído por <b>" . auth()->user()->name . "</b>"
        );

        $deleted = '//deleted_' . md5(uniqid());
        $user->email = $user->email . $deleted;
        $user->cpf = !empty($user->cpf) ? $user->cpf . $deleted : null;

        $user->save();
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
