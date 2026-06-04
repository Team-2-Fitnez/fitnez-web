<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Fitnez administrator']
        );

        $memberRole = Role::query()->firstOrCreate(
            ['name' => 'member'],
            ['description' => 'Fitnez member/user']
        );

        $trainerRole = Role::query()->firstOrCreate(
            ['name' => 'trainer'],
            ['description' => 'Fitnez personal trainer']
        );


        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@fitnez.test'],
            [
                'full_name' => 'Fitnez Admin',
                'phone' => '080000000001',
                'role_id' => $adminRole->id,
                'password_hash' => Hash::make('FitnezTeam2@2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $trainerUser = User::query()->updateOrCreate(
            ['email' => 'trainer@fitnez.test'],
            [
                'full_name' => 'Fitnez Approved Trainer',
                'phone' => '080000000002',
                'role_id' => $trainerRole->id,
                'password_hash' => Hash::make('FitnezTeam2@2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'member@fitnez.test'],
            [
                'full_name' => 'Fitnez Member',
                'phone' => '080000000003',
                'role_id' => $memberRole->id,
                'password_hash' => Hash::make('FitnezTeam2@2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        TrainerApplication::query()->updateOrCreate(
            ['user_id' => $trainerUser->id, 'status' => 'approved'],
            [
                'cv_document_url' => 'dummy/approved-trainer-cv.pdf',
                'certificate_document_url' => 'dummy/approved-trainer-certificate.pdf',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by_admin_id' => $admin->id,
                'admin_notes' => 'Dummy trainer account for team testing.',
            ]
        );

        TrainerDetail::query()->updateOrCreate(
            ['user_id' => $trainerUser->id],
            [
                'specialization' => 'Strength Training',
                'biography' => 'Dummy approved trainer account for Fitnez testing.',
                'experience_years' => 3,
                'hourly_rate' => 150000,
                'avg_rating' => 4.8,
            ]
        );
    }
}
