<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProspectiveMemberRegistration;
use App\Models\TrainerApplication;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = max((int) $request->integer('page', 1), 1);
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 50);

        $pendingMembers = ProspectiveMemberRegistration::orderByDesc('id')
            ->get()
            ->map(fn($item) => [
                'id' => 'member_' . $item->id,
                'real_id' => $item->id,
                'type' => 'member',
                'name' => $item->full_name,
                'email' => $item->email,
                'plan' => $item->package?->name ?? 'Basic',
                'status' => $item->status,
                'created_at' => ($item->created_at ?? now())->toIso8601String(),
            ]);

        $pendingTrainers = TrainerApplication::with('user')->orderByDesc('submitted_at')
            ->get()
            ->map(fn($item) => [
                'id' => 'trainer_' . $item->id,
                'real_id' => $item->id,
                'type' => 'trainer',
                'name' => $item->user->full_name ?? 'Unknown Trainer',
                'email' => $item->user->email ?? '-',
                'specialty' => 'Fitness Specialist',
                'status' => $item->status,
                'created_at' => ($item->submitted_at ?? now())->toIso8601String(),
            ]);

        $newUsers = User::with('membershipPackage')->whereHas('role', fn($q) => $q->where('name', 'member'))
            ->orderByDesc('id')
            ->get()
            ->map(fn($item) => [
                'id' => 'new_user_' . $item->id,
                'real_id' => $item->id,
                'type' => 'member',
                'name' => $item->full_name,
                'email' => $item->email,
                'plan' => $item->membershipPackage?->name ?? 'No Plan',
                'status' => $item->is_active ? 'active account' : 'inactive account',
                'created_at' => ($item->created_at ?? now())->toIso8601String(),
            ]);

        $pendingPayments = \App\Models\TrainerBooking::with(['member', 'trainer'])
            ->where('status', \App\Models\TrainerBooking::STATUS_PENDING_PAYMENT)
            ->orderByDesc('id')
            ->get()
            ->map(fn($item) => [
                'id' => 'payment_' . $item->id,
                'real_id' => $item->id,
                'type' => 'payment',
                'name' => ($item->member->full_name ?? 'Unknown') . ' to ' . ($item->trainer->full_name ?? 'Trainer'),
                'email' => 'Proof Uploaded - Total: Rp ' . number_format($item->total_member_price, 0, ',', '.'),
                'plan' => 'Trainer Hire',
                'status' => $item->status,
                'created_at' => ($item->created_at ?? now())->toIso8601String(),
            ]);

        $activeUsersCount = User::where('is_active', true)->count();

        $allNotifications = $pendingMembers
            ->concat($pendingTrainers)
            ->concat($pendingPayments)
            ->concat($newUsers)
            ->sortByDesc(fn($item) => \Carbon\Carbon::parse($item['created_at'])->timestamp)
            ->values();

        $pageItems = $allNotifications
            ->forPage($page, $perPage)
            ->values();

        $paginated = new LengthAwarePaginator(
            $pageItems,
            $allNotifications->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return ApiResponse::success('Admin notifications loaded.', [
            'notifications' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
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
