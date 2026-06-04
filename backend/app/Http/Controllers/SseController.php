<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    public function stream(string $jobId)
    {
        $response = new StreamedResponse(function () use ($jobId) {
            while (true) {
                if (connection_aborted()) break;

                $progress = Cache::get("sse_progress_{$jobId}", 0);
                $status = Cache::get("sse_status_{$jobId}", 'processing');
                $message = Cache::get("sse_message_{$jobId}", '');

                echo "event: progress\n";
                echo "data: " . json_encode([
                    'progress' => (int) $progress,
                    'status' => $status,
                    'message' => $message,
                ]) . "\n\n";
                ob_flush();
                flush();

                if ($status === 'completed' || $status === 'failed') {
                    Cache::forget("sse_progress_{$jobId}");
                    Cache::forget("sse_status_{$jobId}");
                    Cache::forget("sse_message_{$jobId}");
                    break;
                }

                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}
