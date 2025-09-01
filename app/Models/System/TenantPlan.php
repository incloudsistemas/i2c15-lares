<?php

namespace App\Models\System;

use App\Casts\FloatCast;
use App\Enums\DefaultStatusEnum;
use App\Observers\System\TenantPlanObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TenantPlan extends Model
{
    use HasFactory, Sluggable, SoftDeletes;

    use LogsActivity {
        activities as logActivities;
    }

    protected $fillable = [
        'name',
        'slug',
        'complement',
        'monthly_price',
        'monthly_price_notes',
        'annual_price',
        'annual_price_notes',
        'best_benefit_cost',
        'order',
        'status',
        'features',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price'     => FloatCast::class,
            'annual_price'      => FloatCast::class,
            'best_benefit_cost' => 'boolean',
            'status'            => DefaultStatusEnum::class,
            'features'          => 'array',
            'settings'          => 'array',
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

    protected static function booted()
    {
        static::observe(TenantPlanObserver::class);
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

    public function tenantAccounts(): HasMany
    {
        return $this->hasMany(related: TenantAccount::class, foreignKey: 'plan_id');
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

    protected function displayMonthlyPrice(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            $this->monthly_price
                ? number_format($this->monthly_price, 2, ',', '.')
                : null,
        );
    }

    protected function displayAnnualPrice(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string =>
            $this->annual_price
                ? number_format($this->annual_price, 2, ',', '.')
                : null,
        );
    }

    protected function displayAnnualDiscount(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->monthly_price || !$this->annual_price) {
                    return null;
                }

                $discount = ($this->monthly_price * 12) - $this->annual_price;

                return number_format($discount, 2, ',', '.');
            },
        );
    }

    protected function displayAnnualDiscountMargin(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->monthly_price || !$this->annual_price) {
                    return null;
                }

                $totalMonthly = $this->monthly_price * 12;
                $discount = $totalMonthly - $this->annual_price;
                $discountMargin = $totalMonthly > 0
                    ? round(($discount / $totalMonthly) * 100, 2)
                    : 0;

                return number_format($discountMargin, 2, ',', '.') . '%';
            },
        );
    }
}
