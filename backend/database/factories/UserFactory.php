<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $passwordHash;

    public function definition(): array
    {
        $role = Role::query()->firstOrCreate(
            ['name' => 'member'],
            ['description' => 'Fitnez member/user']
        );

        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'role_id' => $role->id,
            'password_hash' => static::$passwordHash ??= Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }
}
