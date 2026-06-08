<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-' . strtoupper(fake()->bothify('####???#')),
            'user_id' => User::factory(),
            'booking_id' => null,
            'payment_type' => fake()->randomElement(['booking', 'membership']),
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'payment_method' => fake()->randomElement(['transfer', 'cash', 'qris']),
            'payment_status' => fake()->randomElement(['pending', 'confirmed', 'rejected']),
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'external_reference' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['payment_status' => 'confirmed']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['payment_status' => 'pending']);
    }

    public function booking(): static
    {
        return $this->state(fn () => ['payment_type' => 'booking']);
    }
}
