<?php

namespace App\Models\System;

use App\Observers\System\RoleObserver;
use App\Services\System\RoleService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as RoleModel;

class Role extends RoleModel
{
    use HasFactory;

    protected static function booted()
    {
        static::observe(RoleObserver::class);
    }

    /**
     * SCOPES.
     *
     */

    public function scopeByAuthUserRoles(Builder $query, User $user): Builder
    {
        $rolesToAvoid = RoleService::getArrayOfRolesToAvoidByAuthUserRoles(user: $user);

        return $query->whereNotIn('id', $rolesToAvoid);
    }
}
