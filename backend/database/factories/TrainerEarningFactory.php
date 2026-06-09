<?php

namespace Database\Factories;

use App\Models\TrainerEarning;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerEarningFactory extends Factory
{
    protected $model = TrainerEarning::class;

    public function definition(): array
    {
        return [
            'trainer_id' => User::factory()->trainer(),
            'payment_id' => Payment::factory()->confirmed(),
            'booking_id' => null,
            'commission_rate' => 0.70,
            'trainer_amount' => fake()->randomFloat(2, 35000, 350000),
            'status' => fake()->randomElement(['pending', 'disbursed']),
            'disbursed_at' => null,
        ];
    }

    public function disbursed(): static
    {
        return $this->state(fn () => [
            'status' => 'disbursed',
            'disbursed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'disbursed_at' => null]);
    }
}
