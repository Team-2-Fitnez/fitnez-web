<?php

namespace App\Support;

class SearchTerm
{
    public static function contains(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        if ($value === '') {
            return null;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);

        return "%{$escaped}%";
    }
}
