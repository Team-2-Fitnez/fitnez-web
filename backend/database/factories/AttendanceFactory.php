<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'user_id' => User::factory(),
            'check_in_time' => $checkIn,
            'check_out_time' => fake()->boolean(70)
                ? (clone $checkIn)->modify('+' . rand(1, 8) . ' hours')
                : null,
            'attendance_type' => fake()->randomElement(['member_checkin', 'trainer_checkin']),
            'booking_id' => null,
        ];
    }

    public function member(): static
    {
        return $this->state(fn () => ['attendance_type' => 'member_checkin']);
    }

    public function trainer(): static
    {
        return $this->state(fn () => ['attendance_type' => 'trainer_checkin']);
    }
}
