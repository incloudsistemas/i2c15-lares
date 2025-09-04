<?php

namespace App\Traits\Polymorphics;

use App\Models\Polymorphics\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Addressable
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(related: Address::class, name: 'addressable');
    }
}
