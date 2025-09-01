<?php

namespace App\Transformers\Polymorphics\ActivityLog;

use App\Enums\DefaultStatusEnum;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class TenantAccountTransformer
{
    public function transform(ActivityLog $activityLog): array
    {
        $attrData = (array) $activityLog->getExtraProperty('attributes');
        $oldData  = (array) $activityLog->getExtraProperty('old');

        $labels = [
            'name'        => __('Nome da conta'),
            'cpf_cnpj'    => __('CPF / CNPJ'),
            'plan'        => __('Preço mensal'),
            'holder_name' => __('Nome do titular da conta'),
            'categories'  => __('Categoria(s)'),
            'emails'      => __('Email(s)'),
            'phones'      => __('Telefone(s) de contato'),
            'complement'  => __('Sobre'),
            'status'      => __('Status'),
            'created_at'  => __('Cadastro'),
        ];

        $fmt = [
            'plan' => fn(mixed $value): ?string =>
            is_null($value) ? null : $value['name'],

            'categories' => fn(mixed $value): ?string =>
            is_null($value) ? null : collect($value)->pluck('name')->filter()->implode(', '),

            'emails' => fn(mixed $value): ?string =>
            implode(', ', array_column($value, 'email')),

            'phones' => fn(mixed $value): ?string =>
            implode(', ', array_column($value, 'number')),

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
