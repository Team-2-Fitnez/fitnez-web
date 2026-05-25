<?php

namespace App\Events;

use App\Models\Notification;
use App\Support\SocketioBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcast(): void
    {
        $payload = [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'notification_type' => $this->notification->notification_type,
            'is_read' => $this->notification->is_read,
            'created_at' => $this->notification->created_at?->toISOString(),
        ];

        SocketioBroadcast::send("notifications.{$this->notification->user_id}", 'new-notification', $payload);
    }
}
