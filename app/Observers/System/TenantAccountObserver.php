<?php

namespace App\Observers\System;

use App\Models\System\TenantAccount;
use App\Services\Polymorphics\ActivityLogService;

class TenantAccountObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(TenantAccount $tenantAccount): void
    {
        //
    }

    /**
     * Handle the Tenant "updated" event.
     */
    public function updated(TenantAccount $tenantAccount): void
    {
        //
    }

    /**
     * Handle the Tenant "deleted" event.
     */
    public function deleted(TenantAccount $tenantAccount): void
    {
        $tenantAccount->load([
            'tenant:id',
            'address',
            'plan:id,name',
            'categories:id,name'
        ]);

        $logService = app()->make(ActivityLogService::class);
        $logService->logDeletedActivity(
            oldRecord: $tenantAccount,
            description: "Conta de cliente <b>{$tenantAccount->name}</b> excluída por <b>" . auth()->user()->name . "</b>"
        );

        $deleted = '//deleted_' . md5(uniqid());
        $tenantAccount->cpf_cnpj = !empty($tenantAccount->cpf_cnpj) ? $tenantAccount->cpf_cnpj . $deleted : null;
        $tenantAccount->save();

        // $tenant = $tenantAccount->tenant;

        // $tenant->id = $tenant->id . $deleted;
        // $tenant->save();

        // foreach ($tenant->domains as $domain) {
        //     $domain->domain = $domain->domain . $deleted;
        //     $domain->save();
        // }

        // $tenant->delete();
    }

    /**
     * Handle the Tenant "restored" event.
     */
    public function restored(TenantAccount $tenantAccount): void
    {
        //
    }

    /**
     * Handle the Tenant "force deleted" event.
     */
    public function forceDeleted(TenantAccount $tenantAccount): void
    {
        //
    }
}
