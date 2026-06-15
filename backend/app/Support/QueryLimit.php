<?php

namespace App\Support;

use Illuminate\Http\Request;

class QueryLimit
{
    public static function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        $value = (int) $request->query('per_page', $request->query('limit', $default));
        return min(max($value, 1), $max);
    }

    public static function limit(Request $request, int $default = 50, int $max = 100): int
    {
        $value = (int) $request->query('limit', $request->query('per_page', $default));
        return min(max($value, 1), $max);
    }
}
