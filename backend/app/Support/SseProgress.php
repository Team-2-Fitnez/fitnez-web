<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class SseProgress
{
    public static function start(string $jobId): void
    {
        Cache::put("sse_progress_{$jobId}", 0, 3600);
        Cache::put("sse_status_{$jobId}", 'processing', 3600);
        Cache::put("sse_message_{$jobId}", '', 3600);
    }

    public static function update(string $jobId, int $progress, string $message = ''): void
    {
        Cache::put("sse_progress_{$jobId}", $progress, 3600);
        if ($message) {
            Cache::put("sse_message_{$jobId}", $message, 3600);
        }
    }

    public static function complete(string $jobId, string $message = 'Selesai'): void
    {
        Cache::put("sse_progress_{$jobId}", 100, 60);
        Cache::put("sse_status_{$jobId}", 'completed', 60);
        Cache::put("sse_message_{$jobId}", $message, 60);
    }

    public static function fail(string $jobId, string $message = 'Gagal'): void
    {
        Cache::put("sse_progress_{$jobId}", 0, 60);
        Cache::put("sse_status_{$jobId}", 'failed', 60);
        Cache::put("sse_message_{$jobId}", $message, 60);
    }
}
