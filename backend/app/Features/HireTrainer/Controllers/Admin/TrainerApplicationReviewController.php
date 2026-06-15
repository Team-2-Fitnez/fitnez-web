<?php

namespace App\Features\HireTrainer\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Features\HireTrainer\Requests\Admin\ApproveTrainerApplicationRequest;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Features\HireTrainer\Requests\Admin\RejectTrainerApplicationRequest;
use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Support\Facades\Storage;

class TrainerApplicationReviewController extends Controller
{
    public function index(IndexTableRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $applications = TrainerApplication::query()
            ->with(['user.role', 'reviewer'])
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('full_name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search);
                });
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('status')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ApiResponse::success('Trainer applications loaded.', $applications);
    }

    public function approve(ApproveTrainerApplicationRequest $request, TrainerApplication $application)
    {
        $data = $request->validated();

        if ($application->status === 'approved') {
            return ApiResponse::error('This trainer application has already been approved.', [], 422);
        }

        $application->forceFill([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $request->user()->id,
            'admin_notes' => $data['admin_notes'] ?? null,
        ])->save();

        TrainerDetail::query()->updateOrCreate(
            ['user_id' => $application->user_id],
            [
                'specialization' => $data['specialization'] ?? 'General Fitness',
                'biography' => $data['biography'] ?? 'Approved Fitnez trainer.',
                'experience_years' => $data['experience_years'] ?? 0,
                'hourly_rate' => $data['hourly_rate'] ?? 0,
                'base_price' => $data['hourly_rate'] ?? 0,
                'avg_rating' => 0,
            ]
        );

        $trainerRoleId = \App\Models\Role::query()->where('name', 'trainer')->value('id');
        if ($trainerRoleId && $application->user) {
            $application->user->update(['role_id' => $trainerRoleId]);
        }

        $notif = Notification::create([
            'user_id' => $application->user_id,
            'title' => 'Trainer Application Approved',
            'body' => 'Congratulations! Your trainer application has been approved. You can now access the Trainer Workspace.',
            'notification_type' => 'trainer_application',
            'is_read' => false,
        ]);

        try {
            broadcast(new NewNotification($notif));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast NewNotification failed: ' . $e->getMessage());
        }

        return ApiResponse::success('Trainer application approved.', $application->fresh(['user.role', 'reviewer']));
    }

    public function reject(RejectTrainerApplicationRequest $request, TrainerApplication $application)
    {
        $data = $request->validated();

        if ($application->status === 'approved') {
            return ApiResponse::error('Approved trainer applications cannot be rejected.', [], 422);
        }

        $application->forceFill([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $request->user()->id,
            'admin_notes' => $data['admin_notes'],
        ])->save();

        $notif = Notification::create([
            'user_id' => $application->user_id,
            'title' => 'Trainer Application Needs Review',
            'body' => 'Your trainer application could not be approved yet. Please review the admin notes and try again.',
            'notification_type' => 'trainer_application',
            'is_read' => false,
        ]);

        try {
            broadcast(new NewNotification($notif));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast NewNotification failed: ' . $e->getMessage());
        }

        return ApiResponse::success('Trainer application rejected.', $application->fresh(['user.role', 'reviewer']));
    }

    public function download(TrainerApplication $application, string $type)
    {
        $path = match ($type) {
            'cv' => $application->cv_document_url,
            'certificate' => $application->certificate_document_url,
            default => null,
        };

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return ApiResponse::error('Requested trainer document was not found.', [], 404);
        }

        return Storage::disk('local')->download($path, $type.'-'.$application->id.'.pdf');
    }

    /**
     * Stream the document for browser viewing (via fetch with Authorization header).
     */
    public function stream(TrainerApplication $application, string $type)
    {
        $path = match ($type) {
            'cv' => $application->cv_document_url,
            'certificate' => $application->certificate_document_url,
            default => null,
        };

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return ApiResponse::error('Requested trainer document was not found.', [], 404);
        }

        return Storage::disk('local')->response($path);
    }
}
