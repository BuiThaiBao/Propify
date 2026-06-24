<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Sinh biểu thức SQL chuẩn hoá (bỏ dấu/đồng nhất chữ thường) cho một cột,
 * tuỳ theo driver CSDL. Tách ra để cả Repository lẫn các SearchFieldStrategy
 * dùng chung một logic (tránh lặp/khác biệt).
 */
final class ListingSearchExpression
{
    public static function column(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "normalize_text($column)",
            'mysql', 'mariadb' => "LOWER(CONVERT($column USING utf8mb4)) COLLATE utf8mb4_unicode_ci",
            default => "LOWER($column)",
        };
    }
}
