<?php

namespace App\Observers\Crm\Contacts;

use App\Models\Crm\Contacts\Individual;
use App\Services\Polymorphics\ActivityLogService;

class IndividualObserver
{
    /**
     * Handle the Individual "created" event.
     */
    public function created(Individual $individual): void
    {
        //
    }

    /**
     * Handle the Individual "updated" event.
     */
    public function updated(Individual $individual): void
    {
        //
    }

    public function deleted(Individual $individual): void
    {
        $individual->load([
            'contact',
            'contact.owner:id,name',
            'contact.roles:id,name',
            'contact.source:id,name',
            'addresses',
            'legalEntities'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $individual,
            description: "Contato <b>{$individual->contact->name}</b> excluído por <b>" . auth()->user()->name . "</b>"
        );

        $individual->cpf = !empty($individual->cpf) ? $individual->cpf . '//deleted_' . md5(uniqid()) : null;
        $individual->save();

        $individual->contact->email = !empty($individual->contact->email) ? $individual->contact->email . '//deleted_' . md5(uniqid()) : null;
        $individual->contact->save();

        $individual->contact->delete();
    }

    /**
     * Handle the Individual "restored" event.
     */
    public function restored(Individual $individual): void
    {
        //
    }

    /**
     * Handle the Individual "force deleted" event.
     */
    public function forceDeleted(Individual $individual): void
    {
        //
    }
}
