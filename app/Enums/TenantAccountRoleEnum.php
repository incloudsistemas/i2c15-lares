<?php

namespace App\Enums;

use App\Traits\EnumHelper;
use Filament\Support\Contracts\HasLabel;

enum TenantAccountRoleEnum: string implements HasLabel
{
    use EnumHelper;

    case DEFAULT = '1';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEFAULT => 'Padrão',
        };
    }
}
