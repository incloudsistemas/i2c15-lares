<?php

namespace App\Traits\Crm\Contacts;

use App\Models\Crm\Contacts\Contact;
use App\Models\Polymorphics\Address;
use App\Traits\Polymorphics\Addressable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait Contactable
{
    use HasFactory, Addressable, InteractsWithMedia, SoftDeletes;

    use LogsActivity {
        activities as logActivities;
    }

    public function getActivitylogOptions(): LogOptions
    {
        $logName = MorphMapByClass(model: self::class);

        return LogOptions::defaults()
            ->logOnly([])
            ->dontSubmitEmptyLogs()
            ->useLogName($logName);
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

    public function contact(): MorphOne
    {
        return $this->morphOne(related: Contact::class, name: 'contactable');
    }

    /**
     * CUSTOMS.
     *
     */

    public function getMainAddressAttribute(): ?Address
    {
        return $this->addresses()
            ->orderByDesc('is_main')
            ->first();
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
