<?php

namespace App\Observers\System;

use App\Models\System\TenantPlan;
use App\Services\Polymorphics\ActivityLogService;

class TenantPlanObserver
{
    /**
     * Handle the TenantPlan "created" event.
     */
    public function created(TenantPlan $tenantPlan): void
    {
        //
    }

    /**
     * Handle the TenantPlan "updated" event.
     */
    public function updated(TenantPlan $tenantPlan): void
    {
        //
    }

    /**
     * Handle the TenantPlan "deleted" event.
     */
    public function deleted(TenantPlan $tenantPlan): void
    {
        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $tenantPlan,
            description: "Plano <b>{$tenantPlan->name}</b> excluído por <b>" . auth()->user()->name . "</b>"
        );

        $tenantPlan->slug = $tenantPlan->slug . '//deleted_' . md5(uniqid());
        $tenantPlan->save();
    }

    /**
     * Handle the TenantPlan "restored" event.
     */
    public function restored(TenantPlan $tenantPlan): void
    {
        //
    }

    /**
     * Handle the TenantPlan "force deleted" event.
     */
    public function forceDeleted(TenantPlan $tenantPlan): void
    {
        //
    }
}
