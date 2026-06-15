<?php

namespace App\Features\Auth\Services\Auth;

use App\Models\TrainerApplication;
use App\Models\User;

class AuthService
{
    public function userPayload(User $user): array
    {
        $user->loadMissing('role');

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'age' => $user->age,
            'profile_picture_url' => $user->profile_picture_url,
            'role' => $user->role?->name ?? 'member',
            'is_active' => (bool) $user->is_active,
            'membership_status' => $user->membershipStatus(),
            'membership_days_left' => $user->membershipDaysLeft(),
            'can_access_trainer_workspace' => TrainerApplication::query()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->exists(),
        ];
    }
}
