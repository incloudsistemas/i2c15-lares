<?php

namespace App\Models\Crm\Contacts;

use App\Observers\Crm\Contacts\LegalEntityObserver;
use App\Traits\Crm\Contacts\Contactable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;

class LegalEntity extends Model implements HasMedia
{
    use Contactable;

    protected $table = 'crm_contact_legal_entities';

    public $timestamps = false;

    protected $fillable = [
        'trade_name',
        'cnpj',
        'municipal_registration',
        'state_registration',
        'url',
        'sector',
        'num_employees',
        'monthly_income',
    ];

    protected static function booted(): void
    {
        static::observe(LegalEntityObserver::class);
    }

    /**
     * RELATIONSHIPS.
     *
     */

    public function individuals(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Individual::class,
            table: 'crm_contact_individual_crm_contact_legal_entity',
            foreignPivotKey: 'legal_entity_id',
            relatedPivotKey: 'individual_id'
        );
    }
}
