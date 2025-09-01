<?php

namespace App\Models\System;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Casts\DateCast;
use App\Enums\ProfileInfos\EducationalLevelEnum;
use App\Enums\ProfileInfos\GenderEnum;
use App\Enums\ProfileInfos\MaritalStatusEnum;
use App\Enums\ProfileInfos\UserStatusEnum;
use App\Models\Polymorphics\Address;
use App\Observers\System\UserObserver;
use App\Services\System\RoleService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia
{
    use HasFactory, Notifiable, HasRoles, InteractsWithMedia, SoftDeletes;

    use LogsActivity {
        activities as logActivities;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'additional_emails',
        'phones',
        'cpf',
        'rg',
        'gender',
        'birth_date',
        'marital_status',
        'educational_level',
        'nationality',
        'citizenship',
        'complement',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'additional_emails' => 'array',
            'phones'            => 'array',
            'gender'            => GenderEnum::class,
            'birth_date'        => DateCast::class,
            'marital_status'    => MaritalStatusEnum::class,
            'educational_level' => EducationalLevelEnum::class,
            'status'            => UserStatusEnum::class,
        ];
    }

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
        static::observe(UserObserver::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 150, 150)
            ->nonQueued();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ((int) $this->status->value !== 1) {
            // auth()->logout();
            return false;
        }

        return true;
    }

    /**
     * RELATIONSHIPS.
     *
     */

    public function address(): MorphOne
    {
        return $this->morphOne(related: Address::class, name: 'addressable');
    }

    /**
     * SCOPES.
     *
     */

    public function scopeByAuthUserRoles(Builder $query, User $user): Builder
    {
        $rolesToAvoid = RoleService::getArrayOfRolesToAvoidByAuthUserRoles(user: $user);

        return $query->whereHas('roles', function (Builder $query) use ($rolesToAvoid): Builder {
            return $query->whereNotIn('id', $rolesToAvoid);
        });
    }

    public function scopeWhereHasRolesAvoidingClients(Builder $query): Builder
    {
        $rolesToAvoid = [2]; // 2 - Cliente

        return $query->whereHas('roles', function (Builder $query) use ($rolesToAvoid): Builder {
            return $query->whereNotIn('id', $rolesToAvoid);
        });
    }

    public function scopeByStatuses(Builder $query, array $statuses = [1]): Builder
    {
        return $query->whereHasRolesAvoidingClients()
            ->whereIn('status', $statuses);
    }

    /**
     * CUSTOMS.
     *
     */

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

    protected function displayBirthDate(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            $this->birth_date
                ? ConvertEnToPtBrDate(date: $this->birth_date)
                : null,
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
