<?php

namespace App\Transformers\Polymorphics\ActivityLog;

use App\Enums\DefaultStatusEnum;
use App\Enums\ProfileInfos\EducationalLevelEnum;
use App\Enums\ProfileInfos\GenderEnum;
use App\Enums\ProfileInfos\MaritalStatusEnum;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class UserTransformer
{
    public function transform(ActivityLog $activityLog): array
    {
        $attrData = (array) $activityLog->getExtraProperty('attributes');
        $oldData  = (array) $activityLog->getExtraProperty('old');

        $labels = [
            'name'              => __('Nome'),
            'email'             => __('Email'),
            'additional_emails' => __('Email(s) adicional(is)'),
            'phones'            => __('Telefone(s) de contato'),
            'roles'             => __('Nível(is) de acesso(s)'),
            'cpf'               => __('CPF'),
            'rg'                => __('RG'),
            'gender'            => __('Sexo'),
            'birth_date'        => __('Dt. nascimento'),
            'marital_status'    => __('Estado civil'),
            'educational_level' => __('Escolaridade'),
            'nationality'       => __('Nacionalidade'),
            'citizenship'       => __('Naturalidade'),
            'complement'        => __('Sobre'),
            'status'            => __('Status'),
            'created_at'        => __('Cadastro'),
        ];

        $fmt = [
            'additional_emails' => fn(mixed $value): ?string =>
            implode(', ', array_column($value, 'email')),

            'phones' => fn(mixed $value): ?string =>
            implode(', ', array_column($value, 'number')),

            'roles' => fn(mixed $value): ?string =>
            is_null($value) ? null : collect($value)->pluck('name')->filter()->implode(', '),

            'gender' => fn(mixed $value): ?string =>
            is_null($value) ? null : GenderEnum::tryFrom((string) $value)->getLabel(),

            'birth_date' => fn(mixed $value): ?string =>
            is_null($value) ? null : Carbon::parse($value)->format('d/m/Y'),

            'marital_status' => fn(mixed $value): ?string =>
            is_null($value) ? null : MaritalStatusEnum::tryFrom((string) $value)->getLabel(),

            'educational_level' => fn(mixed $value): ?string =>
            is_null($value) ? null : EducationalLevelEnum::tryFrom((string) $value)->getLabel(),

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
