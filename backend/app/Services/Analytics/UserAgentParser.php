<?php

namespace App\Services\Analytics;

class UserAgentParser
{
    public function parse(?string $userAgent): array
    {
        $ua = strtolower((string) $userAgent);

        return [
            'browser_name' => $this->browserName($ua),
            'browser_version' => $this->browserVersion((string) $userAgent),
            'os_name' => $this->osName($ua),
            'device_type' => $this->deviceType($ua),
        ];
    }

    private function browserName(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'edg/') || str_contains($ua, 'edge/') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'chrome/') || str_contains($ua, 'chromium/') => 'Chrome',
            str_contains($ua, 'safari/') => 'Safari',
            default => 'Unknown',
        };
    }

    private function browserVersion(string $userAgent): ?string
    {
        foreach ([
            '/(?:edg|edge)\/([\d.]+)/i',
            '/(?:opr|opera)\/([\d.]+)/i',
            '/firefox\/([\d.]+)/i',
            '/(?:chrome|chromium)\/([\d.]+)/i',
            '/version\/([\d.]+).*safari/i',
        ] as $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return $matches[1] ?? null;
            }
        }

        return null;
    }

    private function osName(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios') => 'iOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Unknown',
        };
    }

    private function deviceType(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'mobile',
            default => 'desktop',
        };
    }
}
