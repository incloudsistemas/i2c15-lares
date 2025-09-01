<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Morph map for polymorphic relations.
        Relation::morphMap([
            'tenants'           => 'App\Models\System\Tenant',
            'tenant_plans'      => 'App\Models\System\TenantPlan',
            'tenant_accounts'   => 'App\Models\System\TenantAccount',
            'tenant_categories' => 'App\Models\System\TenantCategory',
            'users'             => 'App\Models\System\User',

            'addresses' => 'App\Models\Polymorphics\Address',
            'media'     => 'Spatie\MediaLibrary\MediaCollections\Models\Media',
        ]);
    }
}
