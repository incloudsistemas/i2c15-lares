<?php

namespace App\Models\Crm;

use App\Enums\DefaultStatusEnum;
use App\Models\Crm\Contacts\Contact;
use App\Observers\Crm\SourceObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Source extends Model
{
    use HasFactory, Sluggable, SoftDeletes;

    protected $table = 'crm_sources';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => DefaultStatusEnum::class,
    ];

    protected static function booted(): void
    {
        static::observe(SourceObserver::class);
    }

    public function sluggable(): array
    {
        if (!empty($this->slug)) {
            return [];
        }

        return [
            'slug' => [
                'source'   => 'name',
                'onUpdate' => true,
            ],
        ];
    }

    /**
     * RELATIONSHIPS.
     *
     */

    // public function business(): HasMany
    // {
    //     return $this->hasMany(related: Business::class, foreignKey: 'source_id');
    // }

    public function contacts(): HasMany
    {
        return $this->hasMany(related: Contact::class, foreignKey: 'source_id');
    }

    /**
     * SCOPES.
     *
     */

    public function scopeByStatuses(Builder $query, array $statuses = [1]): Builder
    {
        return $query->whereIn('status', $statuses);
    }
}
