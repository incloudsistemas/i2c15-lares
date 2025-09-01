<?php

namespace App\Models\System;

use App\Observers\System\PermissionObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
    use HasFactory;

    protected static function booted()
    {
        static::observe(PermissionObserver::class);
    }
}
