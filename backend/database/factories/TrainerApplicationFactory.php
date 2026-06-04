<?php

namespace Database\Factories;

use App\Models\TrainerApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerApplicationFactory extends Factory
{
    protected $model = TrainerApplication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cv_document_url' => 'trainer-applications/test/cv.pdf',
            'certificate_document_url' => 'trainer-applications/test/certificate.pdf',
            'status' => 'pending',
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by_admin_id' => null,
            'admin_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by_admin_id' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => User::factory(),
            'admin_notes' => 'Application approved.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => User::factory(),
            'admin_notes' => 'Application rejected.',
        ]);
    }
}
