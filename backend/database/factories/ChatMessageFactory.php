<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'message' => fake()->sentence(),
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(fn() => ['is_read' => true]);
    }

    public function unread(): static
    {
        return $this->state(fn() => ['is_read' => false]);
    }

    public function withFile(): static
    {
        return $this->state(fn() => [
            'file_url' => '/storage/chat-files/example.pdf',
            'file_name' => 'document.pdf',
            'file_size' => 2048,
        ]);
    }
}
