<?php

namespace App\Transformers\Polymorphics\ActivityLog;

use App\Enums\DefaultStatusEnum;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class TenantPlanTransformer
{
    public function transform(ActivityLog $activityLog): array
    {
        $attrData = (array) $activityLog->getExtraProperty('attributes');
        $oldData  = (array) $activityLog->getExtraProperty('old');

        $labels = [
            'name'                => __('Nome'),
            'complement'          => __('Sobre'),
            'monthly_price'       => __('Preço mensal'),
            'monthly_price_notes' => __('Observações do preço mensal'),
            'annual_price'        => __('Preço anual'),
            'annual_price_notes'  => __('Observações do preço anual'),
            'features'            => __('Recursos do plano'),
            'settings'            => __('Configs. do plano'),
            'status'              => __('Status'),
            'created_at'          => __('Cadastro'),
        ];

        $fmt = [
            'monthly_price' => fn(mixed $value): ?string =>
            is_null($value) ? null : number_format($value, 2, ',', '.'),

            'annual_price' => fn(mixed $value): ?string =>
            is_null($value) ? null : number_format($value, 2, ',', '.'),

            'features' => fn(mixed $value): ?string =>
            is_null($value) ? null : collect($value)->pluck('name')->filter()->implode(', '),

            'settings' => fn(mixed $value): ?string =>
            is_null($value) ? null : implode(', ', $value),

            'status' => fn(mixed $value): ?string =>
            is_null($value) ? null : DefaultStatusEnum::tryFrom((string) $value)->getLabel(),

            'created_at' => fn(mixed $value): ?string =>
            is_null($value) ? null : Carbon::parse($value)->format('d/m/Y'),
        ];

        $changes = [];
        foreach ($labels as $key => $label) {
            $attr = $attrData[$key] ?? null;
            $old  = $oldData[$key] ?? null;

            $display = $fmt[$key] ?? fn(mixed $value): ?string => $value;

            $attrDisplay = $display($attr);
            $oldDisplay = $display($old);

            if ($attrDisplay === null && $oldDisplay === null) {
                continue;
            }

            if ($attrDisplay === $oldDisplay) {
                continue;
            }

            $changes[] = [
                'label' => $label,
                'attr'  => $attrDisplay,
                'old'   => $oldDisplay,
            ];
        }

        return [
            'changes' => $changes
        ];
    }
}
