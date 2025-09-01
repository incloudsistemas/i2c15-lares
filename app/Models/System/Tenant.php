<?php

namespace App\Models\System;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasFactory, Sluggable;

    public function sluggable(): array
    {
        if (!empty($this->id)) {
            return [];
        }

        return [
            'id' => [
                'source'   => 'id',
                'onUpdate' => true,
            ],
        ];
    }

    /**
     * RELATIONSHIPS.
     *
     */

    public function account(): HasOne
    {
        return $this->hasOne(related: TenantAccount::class, foreignKey: 'tenant_id');
    }
}
