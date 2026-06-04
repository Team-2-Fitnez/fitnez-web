<?php
namespace App\Support;
class UserAgent {
    public static function hash(?string $userAgent): ?string { return $userAgent ? hash('sha256', $userAgent) : null; }
    public static function browserName(?string $userAgent): ?string {
        if (!$userAgent) return null; $ua = strtolower($userAgent);
        return match(true) { str_contains($ua,'edg/')=>'Edge', str_contains($ua,'chrome/')=>'Chrome', str_contains($ua,'firefox/')=>'Firefox', str_contains($ua,'safari/')=>'Safari', default=>'Unknown' };
    }
}
