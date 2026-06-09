<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProspectiveMemberRegistration;
use App\Models\TrainerApplication;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $pendingMembers = ProspectiveMemberRegistration::orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => 'member_' . $item->id,
                'real_id' => $item->id,
                'type' => 'member',
                'name' => $item->full_name,
                'email' => $item->email,
                'plan' => $item->package?->name ?? 'Basic',
                'status' => $item->status,
                'created_at' => ($item->created_at ?? now())->toDateTimeString(),
            ]);

        $pendingTrainers = TrainerApplication::with('user')->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => 'trainer_' . $item->id,
                'real_id' => $item->id,
                'type' => 'trainer',
                'name' => $item->user->full_name ?? 'Unknown Trainer',
                'email' => $item->user->email ?? '-',
                'specialty' => 'Fitness Specialist',
                'status' => $item->status,
                'created_at' => ($item->created_at ?? now())->toDateTimeString(),
            ]);

        $activeUsersCount = User::where('is_active', true)->count();
        

        $systemNotifs = collect([[
            'id' => 'system_active_users',
            'real_id' => 0,
            'type' => 'system',
            'name' => 'Status Sistem',
            'email' => 'Total Akun Aktif Saat Ini',
            'plan' => $activeUsersCount . ' Akun Aktif',
            'status' => 'info',
            'created_at' => now()->toDateTimeString(),
        ]]);

        $allNotifications = $pendingMembers
            ->concat($pendingTrainers)
            ->concat($systemNotifs)
            ->sortByDesc('created_at')
            ->values();

        return ApiResponse::success('Admin notifications loaded.', [
            'notifications' => $allNotifications,
        ]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        if (str_starts_with($id, 'member_')) {
            $realId = str_replace('member_', '', $id);
            $reg = ProspectiveMemberRegistration::find($realId);
            if ($reg) {
                if ($reg->status !== 'awaiting_admin_review') {
                    return ApiResponse::error('Registration is not in a state that can be approved.', [], 422);
                }
                

                $reg->update([
                    'status' => 'approved',
                    'admin_id' => $request->user()->id,
                    'approved_at' => now(),
                ]);
                
                return ApiResponse::success('Member registration approved.');
            }
        } elseif (str_starts_with($id, 'trainer_')) {
            $realId = str_replace('trainer_', '', $id);
            $app = TrainerApplication::find($realId);
            if ($app) {
                if ($app->status === 'approved') {
                    return ApiResponse::error('Application already approved.', [], 422);
                }

                $app->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by_admin_id' => $request->user()->id,
                ]);

                return ApiResponse::success('Trainer application approved.');
            }
        }

        return ApiResponse::error('Registration/Application not found.', [], 404);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        if (str_starts_with($id, 'member_')) {
            $realId = str_replace('member_', '', $id);
            $reg = ProspectiveMemberRegistration::find($realId);
            if ($reg) {
                $reg->update([
                    'status' => 'rejected',
                    'admin_id' => $request->user()->id,
                    'rejected_at' => now(),
                ]);
                return ApiResponse::success('Member registration rejected.');
            }
        } elseif (str_starts_with($id, 'trainer_')) {
            $realId = str_replace('trainer_', '', $id);
            $app = TrainerApplication::find($realId);
            if ($app) {
                $app->update([
                    'status' => 'rejected',
                    'reviewed_at' => now(),
                    'reviewed_by_admin_id' => $request->user()->id,
                ]);
                return ApiResponse::success('Trainer application rejected.');
            }
        }

        return ApiResponse::error('Registration/Application not found.', [], 404);
    }
}
