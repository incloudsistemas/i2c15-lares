<?php

namespace App\Observers\System;

use App\Models\System\Agency;
use App\Services\Polymorphics\ActivityLogService;

class AgencyObserver
{
    /**
     * Handle the Agency "created" event.
     */
    public function created(Agency $agency): void
    {
        //
    }

    /**
     * Handle the Agency "updated" event.
     */
    public function updated(Agency $agency): void
    {
        //
    }

    /**
     * Handle the Agency "deleted" event.
     */
    public function deleted(Agency $agency): void
    {
        $agency->load([
            'users:id,name',
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $agency,
            description: "Agência <b>{$agency->name}</b> excluída por <b>" . auth()->user()->name . "</b>"
        );

        $agency->slug = $agency->slug . '//deleted_' . md5(uniqid());
        $agency->save();
    }

    /**
     * Handle the Agency "restored" event.
     */
    public function restored(Agency $agency): void
    {
        //
    }

    /**
     * Handle the Agency "force deleted" event.
     */
    public function forceDeleted(Agency $agency): void
    {
        //
    }
}
