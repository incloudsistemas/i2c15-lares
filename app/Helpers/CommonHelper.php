<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Retrieves the model type from the morph map.
 *
 */
if (!function_exists('MorphMapByClass')) {
    function MorphMapByClass(string $model): string
    {
        return array_search($model, Relation::morphMap(), true) ?: $model;
    }
}

/**
 * Converts a pt-br formatted float string to an integer.
 *
 */
if (!function_exists('ConvertPtBrFloatStringToInt')) {
    function ConvertPtBrFloatStringToInt(mixed $value): int
    {
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);

        return round(floatval($value) * 100);
    }
}

/**
 * Converts an integer value into a float.
 *
 */
if (!function_exists('ConvertIntToFloat')) {
    function ConvertIntToFloat(mixed $value): float
    {
        return round(floatval($value) / 100, precision: 2);
    }
}

/**
 * Converts a date from pt-br format (dd/mm/yyyy) to en format (yyyy-mm-dd).
 *
 */
if (!function_exists('ConvertPtBrToEnDate')) {
    function ConvertPtBrToEnDate(string $date): string
    {
        return date("Y-m-d", strtotime(str_replace('/', '-', $date)));
    }
}

/**
 * Converts a datetime from pt-br format (dd/mm/yyyy H:i:s) to en format (yyyy-mm-dd H:i:s)
 *
 */
if (!function_exists('ConvertPtBrToEnDateTime')) {
    function ConvertPtBrToEnDateTime(string $date): string
    {
        return date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $date)));
    }
}

/**
 * Converts a date from pt-br format to a full-length date format in pt-br.
 *
 */
if (!function_exists('ConvertPtBrToLongDate')) {
    function ConvertPtBrToLongDate(string $date): string
    {
        $weekday = [
            'Sunday'    => 'Domingo',
            'Monday'    => 'Segunda-Feira',
            'Tuesday'   => 'Terça-Feira',
            'Wednesday' => 'Quarta-Feira',
            'Thursday'  => 'Quinta-Feira',
            'Friday'    => 'Sexta-Feira',
            'Saturday'  => 'Sábado'
        ];

        $month = [
            'January'   => 'Janeiro',
            'February'  => 'Fevereiro',
            'March'     => 'Março',
            'April'     => 'Abril',
            'May'       => 'Maio',
            'June'      => 'Junho',
            'July'      => 'Julho',
            'August'    => 'Agosto',
            'September' => 'Setembro',
            'October'   => 'Outubro',
            'November'  => 'Novembro',
            'December'  => 'Dezembro'
        ];

        $dateFormat = date("l, d \d\e F \d\e Y", strtotime(str_replace('/', '-', $date)));

        foreach ($weekday as $en => $ptBr) {
            $dateFormat = str_replace($en, $ptBr, $dateFormat);
        }

        foreach ($month as $en => $ptBr) {
            $dateFormat = str_replace($en, $ptBr, $dateFormat);
        }

        return $dateFormat;
    }
}

/**
 * Converts a date from en format (yyyy-mm-dd) to pt-br format (dd/mm/yyyy).
 *
 */
if (!function_exists('ConvertEnToPtBrDate')) {
    function ConvertEnToPtBrDate(string $date): string
    {
        return date("d/m/Y", strtotime($date));
    }
}

/**
 * Converts a datetime from en format (yyyy-mm-dd H:i:s) to pt-br format (dd/mm/yyyy H:i:s).
 *
 */
if (!function_exists('ConvertEnToPtBrDateTime')) {
    function ConvertEnToPtBrDateTime(string $date, bool $showSeconds = false): string
    {
        return $showSeconds ? date("d/m/Y H:i:s", strtotime($date)) : date("d/m/Y H:i", strtotime($date));
    }
}

/**
 * Limits a string to a specific number of characters.
 *
 */
if (!function_exists('LimitCharsFromString')) {
    function LimitCharsFromString(?string $string, int $numChars = 280): ?string
    {
        if (!$string) {
            return null;
        }

        return mb_strlen($string, 'UTF-8') <= $numChars ? $string : mb_substr($string, 0, $numChars, 'UTF-8') . '...';
    }
}

/**
 * Cleans a variable by removing special characters, spaces, and unnecessary content.
 *
 */
if (!function_exists('SanitizeVar')) {
    function SanitizeVar(?string $string): ?string
    {
        if (!$string) {
            return null;
        }

        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];

        return preg_replace($search, $replace, $string);
    }
}

/**
 * Formats numbers with abbreviated notation (e.g., 1K, 1M).
 *
 */
if (!function_exists('AbbrNumberFormat')) {
    function AbbrNumberFormat(int $number): string
    {
        if ($number < 1000) {
            return Number::format($number, 0);
        }

        return $number < 1000000
            ? Number::format($number / 1000, 2) . 'k'
            : Number::format($number / 1000000, 2) . 'm';
    };
}

/**
 * Extracts the path from a given URL.
 *
 */
if (!function_exists('GetUrlPath')) {
    function GetUrlPath(string $url): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'];

        $path = str_replace('/storage/', '', $path);

        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);

        return $fullPath;
    };
}

/**
 * Converts a hexadecimal color to RGB format.
 *
 */
if (!function_exists('HexToRgb')) {
    function HexToRgb(?string $hex): ?array
    {
        if (!$hex) {
            return null;
        }

        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 6) {
            list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
        } elseif (strlen($hex) === 3) {
            list($r, $g, $b) = sscanf($hex, "%1x%1x%1x");
            $r = $r * 17;
            $g = $g * 17;
            $b = $b * 17;
        } else {
            throw new Exception("Invalid hex color");
        }

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}

/**
 * Generates a tel link for a given phone number with a country code.
 *
 */
if (!function_exists('GetPhoneLink')) {
    function GetPhoneLink(?string $phone, string $countryCode = '55'): ?string
    {
        return $phone ? "tel:+{$countryCode}" . preg_replace('/\D/', '', $phone) : null;
    }
}

/**
 * Generates a WhatsApp link for a given phone number with an optional message and country code.
 *
 */
if (!function_exists('GetWhatsappLink')) {
    function GetWhatsappLink(?string $phone, string $countryCode = '55', ?string $text = null): ?string
    {
        if (!$phone) {
            return null;
        }

        $cleanPhone = preg_replace('/\D/', '', $phone);
        $url = "https://wa.me/+{$countryCode}{$cleanPhone}";

        if (!empty($text)) {
            $url .= "?text=" . urlencode($text);
        }

        return $url;
    }
}
