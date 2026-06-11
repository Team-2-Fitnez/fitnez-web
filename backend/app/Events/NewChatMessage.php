<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Support\SocketioBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcast(): void
    {
        $payload = [
            'id'          => $this->message->id,
            'sender_id'   => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message'     => $this->message->message,
            'file_url'    => $this->message->file_url,
            'file_name'   => $this->message->file_name,
            'file_size'   => $this->message->file_size,
            'created_at'  => $this->message->created_at?->toISOString(),
            'is_read'     => $this->message->is_read,
            'sender_name' => $this->message->sender?->full_name ?? '',
        ];

        SocketioBroadcast::send("chat.{$this->message->receiver_id}", 'new-message', $payload);
        SocketioBroadcast::send("chat.{$this->message->sender_id}", 'new-message', $payload);
    }
}
