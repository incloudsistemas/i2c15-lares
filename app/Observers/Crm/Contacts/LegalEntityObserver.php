<?php

namespace App\Observers\Crm\Contacts;

use App\Models\Crm\Contacts\LegalEntity;
use App\Services\Polymorphics\ActivityLogService;

class LegalEntityObserver
{
    /**
     * Handle the LegalEntity "created" event.
     */
    public function created(LegalEntity $legalEntity): void
    {
        //
    }

    /**
     * Handle the LegalEntity "updated" event.
     */
    public function updated(LegalEntity $legalEntity): void
    {
        //
    }

    public function deleted(LegalEntity $legalEntity): void
    {
        $legalEntity->load([
            'contact',
            'contact.owner:id,name',
            'contact.roles:id,name',
            'contact.source:id,name',
            'addresses',
            'individuals'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $legalEntity,
            description: "Contato <b>{$legalEntity->contact->name}</b> excluído por <b>" . auth()->user()->name . "</b>"
        );

        $legalEntity->cnpj = !empty($legalEntity->cnpj) ? $legalEntity->cnpj . '//deleted_' . md5(uniqid()) : null;
        $legalEntity->save();

        $legalEntity->contact->email = !empty($legalEntity->contact->email) ? $legalEntity->contact->email . '//deleted_' . md5(uniqid()) : null;
        $legalEntity->contact->save();

        $legalEntity->contact->delete();
    }

    /**
     * Handle the LegalEntity "restored" event.
     */
    public function restored(LegalEntity $legalEntity): void
    {
        //
    }

    /**
     * Handle the LegalEntity "force deleted" event.
     */
    public function forceDeleted(LegalEntity $legalEntity): void
    {
        //
    }
}
