<?php

namespace Database\Factories;

use App\Models\TrainerDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerDetailFactory extends Factory
{
    protected $model = TrainerDetail::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'specialization' => fake()->randomElement(['Strength Training', 'Yoga', 'Cardio', 'Pilates', 'CrossFit']),
            'biography' => fake()->paragraph(),
            'experience_years' => fake()->numberBetween(1, 20),
            'hourly_rate' => fake()->randomFloat(2, 50000, 300000),
            'base_price' => fake()->randomFloat(2, 50000, 300000),
            'avg_rating' => fake()->randomFloat(2, 3.0, 5.0),
        ];
    }
}
