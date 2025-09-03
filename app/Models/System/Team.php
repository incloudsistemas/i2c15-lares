<?php

namespace App\Models\System;

use App\Enums\DefaultStatusEnum;
use App\Observers\System\TeamObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Team extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Sluggable, SoftDeletes;

    use LogsActivity {
        activities as logActivities;
    }

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'complement',
        'status',
    ];

    protected $casts = [
        'status' => DefaultStatusEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $logName = MorphMapByClass(model: self::class);

        return LogOptions::defaults()
            ->logOnly([])
            ->dontSubmitEmptyLogs()
            ->useLogName($logName);
    }

    protected static function booted(): void
    {
        static::observe(TeamObserver::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 150, 150)
            ->nonQueued();
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

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(related: User::class)
            ->withPivot(columns: 'role')
            ->wherePivot(column: 'role', operator: 2); // 2 - 'Colaborador/Collaborator'
    }

    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(related: User::class)
            ->withPivot(columns: 'role')
            ->wherePivot(column: 'role', operator: 1); // 1 - 'Líder/Leader ou Coordenador/Coordinator'
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(related: User::class)
            ->withPivot(columns: 'role');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(related: Agency::class);
    }

    /**
     * SCOPES.
     *
     */

    public function scopeByStatuses(Builder $query, array $statuses = [1]): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * CUSTOMS.
     *
     */

    protected function featuredImage(): Attribute
    {
        return Attribute::make(
            get: fn(): ?Media =>
            $this->getFirstMedia('avatar') ?: $this->getFirstMedia('images'),
        );
    }

    protected function attachments(): Attribute
    {
        return Attribute::make(
            get: function (): ?Collection {
                $media = $this->getMedia('attachments')->sortBy('order_column');

                return $media->isEmpty() ? null : $media;
            },
        );
    }
}
