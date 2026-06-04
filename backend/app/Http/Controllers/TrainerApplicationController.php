<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trainer\StoreTrainerApplicationRequest;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TrainerApplicationController extends Controller
{
    public function status(Request $request)
    {
        return ApiResponse::success('Trainer application status loaded.', $this->payload($request));
    }

    public function store(StoreTrainerApplicationRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        if ($user->roleName() === 'admin') {
            throw ValidationException::withMessages([
                'role' => ['Admin accounts do not need to apply as trainer.'],
            ]);
        }

        $approved = TrainerApplication::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        if ($approved) {
            throw ValidationException::withMessages([
                'status' => ['Your trainer application has already been approved.'],
            ]);
        }

        $pending = TrainerApplication::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'status' => ['Your previous trainer application is still waiting for admin review.'],
            ]);
        }

        $basePath = "trainer-applications/{$user->id}";
        Storage::disk('local')->makeDirectory($basePath);

        $cvPath = $data['cv']->store($basePath, 'local');
        $certificatePath = $data['certificate']->store($basePath, 'local');

        if (! $cvPath || ! $certificatePath) {
            throw ValidationException::withMessages([
                'files' => ['Unable to store trainer application files. Please check storage permissions.'],
            ]);
        }

        $application = TrainerApplication::query()->create([
            'user_id' => $user->id,
            'cv_document_url' => $cvPath,
            'certificate_document_url' => $certificatePath,
            'status' => 'pending',
            'submitted_at' => now(),
            'admin_notes' => json_encode([
                'specialization' => $data['specialization'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
            ]),
        ]);

        return ApiResponse::success('Trainer application submitted.', $application->load('user.role'), 201);
    }

    public function enterWorkspace(Request $request)
    {
        $user = $request->user();

        if (! $this->canAccessTrainerWorkspace($user->id)) {
            return ApiResponse::error('Trainer workspace is only available after admin approval.', [], 403);
        }

        return ApiResponse::success('Trainer workspace access granted.', [
            'redirect_to' => '/trainer/dashboard',
            'user' => app(\App\Services\Auth\AuthService::class)->userPayload($user),
        ]);
    }

    public function leaveWorkspace(Request $request)
    {
        return ApiResponse::success('Returned to member workspace.', [
            'redirect_to' => '/member/dashboard',
            'user' => app(\App\Services\Auth\AuthService::class)->userPayload($request->user()),
        ]);
    }

    private function payload(Request $request): array
    {
        $user = $request->user();
        $application = TrainerApplication::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        return [
            'application' => $application,
            'status' => $application?->status ?? 'not_submitted',
            'can_access_trainer_workspace' => $this->canAccessTrainerWorkspace($user->id),
            'has_trainer_profile' => TrainerDetail::query()->where('user_id', $user->id)->exists(),
        ];
    }

    private function canAccessTrainerWorkspace(int $userId): bool
    {
        return TrainerApplication::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }
}
