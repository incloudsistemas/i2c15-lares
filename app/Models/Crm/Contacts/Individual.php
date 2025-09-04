<?php

namespace App\Models\Crm\Contacts;

use App\Casts\DateCast;
use App\Enums\ProfileInfos\GenderEnum;
use App\Observers\Crm\Contacts\IndividualObserver;
use App\Traits\Crm\Contacts\Contactable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;

class Individual extends Model implements HasMedia
{
    use Contactable;

    protected $table = 'crm_contact_individuals';

    public $timestamps = false;

    protected $fillable = [
        'cpf',
        'rg',
        'gender',
        'birth_date',
        'occupation',
    ];

    protected $casts = [
        'gender'     => GenderEnum::class,
        'birth_date' => DateCast::class
    ];

    protected static function booted(): void
    {
        static::observe(IndividualObserver::class);
    }

    /**
     * RELATIONSHIPS.
     *
     */

    public function legalEntities(): BelongsToMany
    {
        return $this->belongsToMany(
            related: LegalEntity::class,
            table: 'crm_contact_individual_crm_contact_legal_entity',
            foreignPivotKey: 'individual_id',
            relatedPivotKey: 'legal_entity_id'
        );
    }

    /**
     * CUSTOMS.
     *
     */

    protected function displayBirthDate(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            $this->birth_date
                ? ConvertEnToPtBrDate(date: $this->birth_date)
                : null,
        );
    }
}
