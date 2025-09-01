<?php

namespace App\Enums\ProfileInfos;

use App\Traits\EnumHelper;
use Filament\Support\Contracts\HasLabel;

enum SocialMediaEnum: string implements HasLabel
{
    use EnumHelper;

    case Facebook  = 'facebook';
    case Instagram = 'instagram';
    case Twitter   = 'twitter';
    case YouTube   = 'youtube';
    case TikTok    = 'tiktok';
    case LinkedIn  = 'linkedin';
    case Pinterest = 'pinterest';

    public function getLabel(): string
    {
        return match ($this) {
            self::Facebook  => 'Facebook',
            self::Instagram => 'Instagram',
            self::Twitter   => 'Twitter',
            self::YouTube   => 'YouTube',
            self::TikTok    => 'TikTok',
            self::LinkedIn  => 'LinkedIn',
            self::Pinterest => 'Pinterest',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Facebook  => 'uil-facebook-f',
            self::Instagram => 'uil-instagram',
            self::Twitter   => 'uil-twitter-alt',
            self::YouTube   => 'uil-youtube',
            self::TikTok    => 'bi-tiktok',
            self::LinkedIn  => 'uil-linkedin',
            self::Pinterest => 'bi-pinterest',
        };
    }
}
