<?php

namespace App\Models\Crm\Contacts;

use App\Enums\DefaultStatusEnum;
use App\Observers\Crm\Contacts\RoleObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, Sluggable, SoftDeletes;

    protected $table = 'crm_contact_roles';

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
        static::observe(RoleObserver::class);
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

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Contact::class,
            table: 'crm_contact_crm_contact_role',
            foreignPivotKey: 'role_id',
            relatedPivotKey: 'contact_id'
        );
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
