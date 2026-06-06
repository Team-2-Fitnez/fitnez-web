<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'notification_type' => fake()->randomElement([
                'booking_request', 'booking_confirmed', 'booking_rejected',
                'booking_cancelled', 'booking_completed', 'chat_message',
                'payment_in', 'system',
            ]),
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

    public function ofType(string $type): static
    {
        return $this->state(fn() => ['notification_type' => $type]);
    }

    public function bookingRequest(): static
    {
        return $this->ofType('booking_request');
    }
}
