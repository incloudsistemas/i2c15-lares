<?php

namespace App\Models\Polymorphics;

use App\Enums\ProfileInfos\UfEnum;
use App\Observers\Polymorphics\AddressObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Cviebrock\EloquentSluggable\Sluggable;

class Address extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'name',
        'slug',
        'is_main',
        'zipcode',
        'state',
        'uf',
        'city',
        'country',
        'district',
        'address_line',
        'number',
        'complement',
        'custom_street',
        'custom_block',
        'custom_lot',
        'reference',
        'gmap_coordinates',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'uf'      => UfEnum::class,
    ];

    protected static function booted()
    {
        static::observe(AddressObserver::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source'   => ['city', 'uf.name'],
                'onUpdate' => true,
                'unique'   => false,
            ],
        ];
    }

    /**
     * RELATIONSHIPS.
     *
     */

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * CUSTOMS.
     *
     */

    protected function displayFullAddress(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $components = [];

                $line = trim((string) ($this->address_line ?? ''));
                if ($line !== '') {
                    $components[] = $line;
                }

                $number = trim((string) ($this->number ?? ''));
                if ($number !== '') {
                    $components[] = $number;
                }

                $complement = trim((string) ($this->complement ?? ''));
                if ($complement !== '') {
                    $components[] = $complement;
                }

                $district = trim((string) ($this->district ?? ''));
                if ($district !== '') {
                    $components[] = $district;
                }

                $city = trim((string) ($this->city ?? ''));
                if ($city !== '') {
                    $components[] = $city;

                    $ufName = trim((string) ($this->uf?->name ?? ''));
                    if ($ufName !== '') {
                        $components[] = $ufName;
                    }
                }

                $zipcode = trim((string) ($this->zipcode ?? ''));
                if ($zipcode !== '') {
                    $components[] = $zipcode;
                }

                $result = implode(', ', $components);
                return $result !== '' ? $result : null;
            },
        );
    }

    protected function displayShortAddress(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $components = [];

                $line = trim((string) ($this->address_line ?? ''));
                if ($line !== '') {
                    $components[] = $line;
                }

                $number = trim((string) ($this->number ?? ''));
                if ($number !== '') {
                    $components[] = $number;
                }

                $district = trim((string) ($this->district ?? ''));
                if ($district !== '') {
                    $components[] = $district;
                }

                $result = implode(', ', $components);
                return $result !== '' ? $result : null;
            },
        );
    }
}
