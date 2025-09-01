<?php

namespace App\Observers\System;

use App\Models\System\TenantCategory;

class TenantCategoryObserver
{
    /**
     * Handle the TenantCategory "created" event.
     */
    public function created(TenantCategory $tenantCategory): void
    {
        //
    }

    /**
     * Handle the TenantCategory "updated" event.
     */
    public function updated(TenantCategory $tenantCategory): void
    {
        //
    }

    /**
     * Handle the TenantCategory "deleted" event.
     */
    public function deleted(TenantCategory $tenantCategory): void
    {
        $tenantCategory->slug = $tenantCategory->slug . '//deleted_' . md5(uniqid());

        $tenantCategory->save();
    }

    /**
     * Handle the TenantCategory "restored" event.
     */
    public function restored(TenantCategory $tenantCategory): void
    {
        //
    }

    /**
     * Handle the TenantCategory "force deleted" event.
     */
    public function forceDeleted(TenantCategory $tenantCategory): void
    {
        //
    }
}
