<?php

namespace App\Models\System;

use App\Enums\DefaultStatusEnum;
use App\Enums\TenantAccountRoleEnum;
use App\Models\Polymorphics\Address;
use App\Observers\System\TenantAccountObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TenantAccount extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    use LogsActivity {
        activities as logActivities;
    }

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'role',
        'name',
        'cpf_cnpj',
        'holder_name',
        'emails',
        'phones',
        'complement',
        'social_media',
        'opening_hours',
        'theme',
        'status',
        'settings',
        'custom',
    ];

    protected $casts = [
        'role'          => TenantAccountRoleEnum::class,
        'emails'        => 'array',
        'phones'        => 'array',
        'social_media'  => 'array',
        'opening_hours' => 'array',
        'theme'         => 'array',
        'status'        => DefaultStatusEnum::class,
        'settings'      => 'array',
        'custom'        => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $logName = MorphMapByClass(model: self::class);

        return LogOptions::defaults()
            ->logOnly([])
            ->dontSubmitEmptyLogs()
            ->useLogName($logName);
    }

    protected static function booted()
    {
        static::observe(TenantAccountObserver::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 150, 150)
            ->nonQueued();
    }

    /**
     * RELATIONSHIPS.
     *
     */

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            related: TenantCategory::class,
            table: 'tenant_account_tenant_category',
            foreignPivotKey: 'tenant_account_id',
            relatedPivotKey: 'category_id'
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(related: TenantPlan::class, foreignKey: 'plan_id');
    }

    public function address(): MorphOne
    {
        return $this->morphOne(related: Address::class, name: 'addressable');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(related: Tenant::class, foreignKey: 'tenant_id');
    }

    /**
     * SCOPES.
     *
     */

    public function scopeByRoles(Builder $query, array $roles): Builder
    {
        return $query->whereIn('role', $roles);
    }

    public function scopeByStatuses(Builder $query, array $statuses = [1]): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * CUSTOMS.
     *
     */

    protected function displayMainEmail(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            is_array($this->emails) && isset($this->emails[0]['email'])
                ? $this->emails[0]['email']
                : null,
        );
    }

    protected function displayAdditionalEmails(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $emails = is_array($this->emails) ? $this->emails : [];

                if (!isset($emails[1]['email'])) {
                    return null;
                }

                $additional = [];
                foreach (array_slice($emails, 1) as $email) {
                    if (!isset($email['email'])) {
                        continue;
                    }

                    $text = $email['email'];
                    if (!empty($email['name'])) {
                        $text .= " ({$email['name']})";
                    }

                    $additional[] = $text;
                }

                return !empty($additional) ? $additional : null;
            },
        );
    }

    protected function displayMainPhone(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            is_array($this->phones) && isset($this->phones[0]['number'])
                ? $this->phones[0]['number']
                : null,
        );
    }

    protected function displayMainPhoneWithName(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $phones = is_array($this->phones) ? $this->phones : [];

                if (!isset($phones[0]['number'])) {
                    return null;
                }

                $main  = $phones[0]['number'];
                $name  = $phones[0]['name'] ?? null;

                if (!empty($name)) {
                    $main .= " ({$name})";
                }

                return $main;
            },
        );
    }

    protected function displayAdditionalPhones(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $phones = is_array($this->phones) ? $this->phones : [];

                if (!isset($phones[1]['number'])) {
                    return null;
                }

                $additional = [];
                foreach (array_slice($phones, 1) as $phone) {
                    if (!isset($phone['number'])) {
                        continue;
                    }

                    $text = $phone['number'];
                    if (!empty($phone['name'])) {
                        $text .= " ({$phone['name']})";
                    }

                    $additional[] = $text;
                }

                return !empty($additional) ? $additional : null;
            },
        );
    }

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
