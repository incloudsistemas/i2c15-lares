<?php

namespace App\Models\Crm\Contacts;

use App\Enums\DefaultStatusEnum;
use App\Models\Crm\Source;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Contact extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'contactable_type',
        'contactable_id',
        'user_id',
        'source_id',
        'name',
        'email',
        'additional_emails',
        'phones',
        'complement',
        'status',
        'custom',
    ];

    protected $casts = [
        'additional_emails' => 'array',
        'phones'            => 'array',
        'status'            => DefaultStatusEnum::class,
        'custom'            => 'array',
    ];

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

    // public function activities(): BelongsToMany
    // {
    //     return $this->belongsToMany(
    //         related: Activity::class,
    //         table: 'activity_crm_contact',
    //         foreignPivotKey: 'contact_id',
    //         relatedPivotKey: 'activity_id'
    //     );
    // }

    // public function financialTransactions(): HasMany
    // {
    //     return $this->hasMany(related: Transaction::class, foreignKey: 'contact_id');
    // }

    // public function business(): HasMany
    // {
    //     return $this->hasMany(related: Business::class, foreignKey: 'contact_id');
    // }

    public function source(): BelongsTo
    {
        return $this->belongsTo(related: Source::class, foreignKey: 'source_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Role::class,
            table: 'crm_contact_crm_contact_role',
            foreignPivotKey: 'contact_id',
            relatedPivotKey: 'role_id'
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(related: User::class, foreignKey: 'user_id');
    }

    public function contactable(): MorphTo
    {
        return $this->morphTo();
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

    protected function displayContactableType(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            $this->contactable_type === MorphMapByClass(model: LegalEntity::class)
                ? 'P. Jurídica'
                : 'P. Física'
        );
    }

    protected function displayAdditionalEmails(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $items = is_array($this->additional_emails) ? $this->additional_emails : [];

                $result = collect($items)
                    ->filter(fn($email) => is_array($email) && !empty($email['email']))
                    ->map(fn($email) => $email['email'] . (!empty($email['name']) ? " ({$email['name']})" : ''))
                    ->values()
                    ->all();

                return !empty($result) ? $result : null;
            },
        );
    }

    protected function displayMainPhone(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $phones = is_array($this->phones) ? $this->phones : [];
                return isset($phones[0]['number']) ? $phones[0]['number'] : null;
            },
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

                $number = $phones[0]['number'];
                $name   = $phones[0]['name'] ?? null;

                return $number . (!empty($name) ? " ({$name})" : '');
            },
        );
    }

    protected function displayAdditionalPhones(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $phones = is_array($this->phones) ? $this->phones : [];

                $result = collect($phones)
                    ->slice(1)
                    ->filter(fn($phone) => is_array($phone) && !empty($phone['number']))
                    ->map(fn($phone) => $phone['number'] . (!empty($phone['name']) ? " ({$phone['name']})" : ''))
                    ->values()
                    ->all();

                return !empty($result) ? $result : null;
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
