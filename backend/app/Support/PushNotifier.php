<?php

namespace App\Support;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotifier
{
    public static function send(int $userId, string $title, string $body): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();
        if ($subscriptions->isEmpty()) return;

        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode(['title' => $title, 'body' => $body]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'authToken' => $sub->auth_key,
                    'publicKey' => $sub->p256dh_key,
                    'contentEncoding' => 'aesgcm',
                ]),
                $payload,
            );
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                logger()->warning('Push notification failed: ' . $report->getReason());
                $sub->delete();
            }
        }
    }
}
