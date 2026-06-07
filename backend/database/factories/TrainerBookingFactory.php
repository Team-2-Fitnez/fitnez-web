<?php

namespace Database\Factories;

use App\Models\TrainerBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerBookingFactory extends Factory
{
    protected $model = TrainerBooking::class;

    public function definition(): array
    {
        return [
            'member_id' => User::factory(),
            'trainer_id' => User::factory(),
            'booking_date' => fake()->dateTimeBetween('today', '+1 month')->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'session_type' => 'online',
            'location' => 'Gym Utama',
            'member_notes' => fake()->sentence(),
            'status' => TrainerBooking::STATUS_PENDING,
            'total_price' => fake()->randomFloat(2, 50000, 500000),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => TrainerBooking::STATUS_PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state(fn() => ['status' => TrainerBooking::STATUS_CONFIRMED]);
    }

    public function completed(): static
    {
        return $this->state(fn() => ['status' => TrainerBooking::STATUS_COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => ['status' => TrainerBooking::STATUS_CANCELLED]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => ['status' => TrainerBooking::STATUS_REJECTED]);
    }

    public function online(): static
    {
        return $this->state(fn() => ['session_type' => 'online']);
    }

    public function offline(): static
    {
        return $this->state(fn() => ['session_type' => 'offline', 'location' => 'Gym Utama']);
    }
}
