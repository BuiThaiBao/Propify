<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PropertySearchText
{
    public static function build(array $parts): string
    {
        $normalizedParts = array_values(array_filter(array_map(
            static fn ($value) => self::normalize($value),
            $parts,
        )));

        return implode(' ', $normalizedParts);
    }

    public static function normalize(null|string|int|float $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        $normalized = Str::ascii(mb_strtolower($normalized, 'UTF-8'));

        return preg_replace('/\s+/u', ' ', $normalized) ?: $normalized;
    }
}
