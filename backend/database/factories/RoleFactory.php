<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => ['name' => 'admin', 'description' => 'Administrator']);
    }

    public function member(): static
    {
        return $this->state(fn() => ['name' => 'member', 'description' => 'Member/user']);
    }

    public function trainer(): static
    {
        return $this->state(fn() => ['name' => 'trainer', 'description' => 'Trainer']);
    }
}
