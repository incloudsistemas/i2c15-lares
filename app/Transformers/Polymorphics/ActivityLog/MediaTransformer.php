<?php

namespace App\Transformers\Polymorphics\ActivityLog;

use Spatie\Activitylog\Models\Activity as ActivityLog;

class MediaTransformer
{
    public function transform(ActivityLog $activityLog): array
    {
        $attrData = (array) $activityLog->getExtraProperty('attributes');
        $oldData  = (array) $activityLog->getExtraProperty('old');

        $labels = [
            'name'      => __('Nome'),
            'file_name' => __('Nome do arq.'),
            'mime_type' => __('Mime'),
            'size'      => __('Tamanho'),
        ];

        $fmt = [
            'size' => fn(mixed $value): ?string =>
            is_null($value) ? null : AbbrNumberFormat($value),
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
