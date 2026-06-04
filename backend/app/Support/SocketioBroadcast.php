<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class SocketioBroadcast
{
    protected static string $serverUrl = 'http://socketio:6001';

    public static function setServerUrl(string $url): void
    {
        static::$serverUrl = $url;
    }

    public static function send(string $channel, string $event, array $payload): void
    {
        try {
            Http::timeout(3)->post(static::$serverUrl, [
                'channel' => $channel,
                'event' => $event,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            logger()->warning('Socket.io broadcast failed: ' . $e->getMessage());
        }
    }
}
