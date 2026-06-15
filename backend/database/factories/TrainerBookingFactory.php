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
        $startDate = fake()->dateTimeBetween('today', '+1 week');
        $endDate = (clone $startDate)->modify('+1 month');
        return [
            'member_id' => User::factory(),
            'trainer_id' => User::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'sessions_per_week' => 3,
            'session_days' => ['Monday', 'Wednesday', 'Friday'],
            'session_time' => '10:00',
            'member_notes' => fake()->sentence(),
            'status' => TrainerBooking::STATUS_PENDING,
            'base_price_per_session' => 100000.00,
            'member_price_per_session' => 120000.00,
            'total_member_price' => 1440000.00,
            'total_trainer_price' => 1200000.00,
            'total_sessions' => 12,
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
}
