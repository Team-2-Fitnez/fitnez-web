<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Attendance;
use App\Models\MealPlan;
use App\Models\NutritionCalculator;
use App\Models\Payment;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\TrainerEarning;
use App\Models\WorkoutPlan;
use App\Models\WorkoutTracking;
use App\Models\SystemLog;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function summary()
    {
        return ApiResponse::success('Dashboard summary loaded.', $this->summaryPayload());
    }

    public function adminSummary()
    {
        return ApiResponse::success('Admin dashboard summary loaded.', $this->adminSummaryPayload());
    }

    public function adminDashboard()
    {
        $payload = $this->adminSummaryPayload();

        return ApiResponse::success('Admin dashboard loaded.', [
            ...$payload,
            'summary' => [
                'total_members' => $payload['members_total'],
                'total_trainers' => $payload['trainers_total'],
                'pending_registrations' => $payload['pending_prospective_registrations'],
                'pending_trainer_applications' => $payload['pending_trainer_applications'],
                'payments_total' => $payload['total_member_payments'],
                'payments_paid' => $payload['paid_member_payments'],
                'payments_pending' => $payload['pending_member_payments'],
                'attendance_today' => $payload['today_attendance_count'],
                'logins_today' => $payload['logins_today'],
                'registrations_today' => $payload['new_registrations'],
            ],
            'charts' => [
                'payment_status' => $payload['payment_status_breakdown'],
                'attendance_by_day' => $payload['attendance_by_day'],
                'auth_activity_by_day' => $payload['auth_activity_by_day'],
            ],
            'recent' => [
                'auth_activity' => $payload['recent_logins'],
                'payments' => $payload['recent_payment_records'],
                'attendance' => $payload['recent_attendance_records'],
            ],
        ]);
    }

    public function trainerSummary(Request $request)
    {
        return ApiResponse::success('Trainer dashboard summary loaded.', $this->trainerSummaryPayload($request));
    }

    public function trainerDashboard(Request $request)
    {
        $payload = $this->trainerSummaryPayload($request);

        return ApiResponse::success('Trainer dashboard loaded.', [
            ...$payload,
            'summary' => [
                'connected_members' => $payload['total_monitored_members'],
                'active_workout_plans' => $payload['active_workout_plans'],
                'completed_exercises_this_week' => $payload['completed_exercises_this_week'],
                'active_meal_plans' => $payload['meal_plans'],
                'recent_nutrition_records' => $payload['recent_nutrition_records'],
                'total_earnings' => $payload['total_earnings'],
                'paid_earnings' => $payload['total_paid_income'],
                'pending_earnings' => $payload['pending_income'],
                'earnings_this_month' => $payload['earnings_this_month'],
            ],
            'charts' => [
                'earnings_by_month' => $payload['earnings_by_month'],
                'workout_completion' => $payload['workout_completion'],
                'member_activity' => $payload['member_activity'],
            ],
            'recent' => [
                'member_activity' => $payload['recent_monitored_member_activity'],
                'rent_history' => $payload['recent_trainer_payments'],
            ],
        ]);
    }

    public function stream(Request $request): StreamedResponse
    {
        return response()->stream(function () {
            for ($i = 0; $i < 60; $i++) {
                echo "event: dashboard\n";
                echo 'data: '.json_encode($this->summaryPayload())."\n\n";

                @ob_flush();
                flush();

                sleep(5);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function summaryPayload(): array
    {
        return [
            'users_total' => User::query()->count(),
            'new_registrations_today' => User::query()->whereDate('created_at', today())->count(),
            'trainers_total' => TrainerDetail::query()->count(),
            'members_total' => User::query()->whereHas('role', fn ($q) => $q->where('name', 'member'))->count(),
            'schedules_today' => TrainerBooking::query()->whereDate('booking_date', today())->count(),
            'transactions_pending' => Schema::hasTable('payments')
                ? Payment::query()->where('payment_status', 'pending')->count()
                : 0,
            'unread_notifications' => Notification::query()->where('is_read', false)->count(),
            'recent_activity' => User::query()
                ->latest('id')
                ->limit(5)
                ->get(['id', 'full_name', 'email', 'created_at']),
            'admin' => $this->adminSummaryPayload(),
            'updated_at' => now()->toISOString(),
        ];
    }

    private function adminSummaryPayload(): array
    {
        return [
            'total_registered_users' => User::query()->count(),
            'members_total' => User::query()->whereHas('role', fn ($q) => $q->where('name', 'member'))->count(),
            'trainers_total' => TrainerDetail::query()->count(),
            'new_registrations' => User::query()->whereDate('created_at', today())->count(),
            'logins_today' => SystemLog::query()->where('action_type', 'LOGIN_SUCCESS')->whereDate('created_at', today())->count(),
            'recent_logins' => SystemLog::query()->where('action_type', 'LOGIN_SUCCESS')->latest('created_at')->limit(8)->with('user.role')->get(),
            'pending_prospective_registrations' => Schema::hasTable('prospective_member_registrations')
                ? \App\Models\ProspectiveMemberRegistration::query()->whereIn('status', ['awaiting_payment', 'awaiting_admin_review'])->count()
                : 0,
            'pending_trainer_applications' => Schema::hasTable('trainer_applications')
                ? \App\Models\TrainerApplication::query()->where('status', 'pending')->count()
                : 0,
            'total_member_payments' => Payment::query()->count(),
            'paid_member_payments' => Payment::query()->where('payment_status', 'paid')->count(),
            'pending_member_payments' => Payment::query()->where('payment_status', 'pending')->count(),
            'total_payment_amount' => (float) Payment::query()->sum('amount'),
            'total_attendance_records' => Attendance::query()->count(),
            'today_attendance_count' => Attendance::query()->whereDate('check_in_time', today())->count(),
            'recent_payment_records' => Payment::query()->with('user.role')->latest('id')->limit(8)->get(),
            'recent_attendance_records' => Attendance::query()->with('user.role')->latest('check_in_time')->limit(8)->get(),
            'payment_status_breakdown' => Payment::query()->selectRaw('payment_status as status, count(*) as total')->groupBy('payment_status')->get(),
            'attendance_by_day' => Attendance::query()->selectRaw('DATE(check_in_time) as date, count(*) as total')->where('check_in_time', '>=', now()->subDays(27)->startOfDay())->groupByRaw('DATE(check_in_time)')->orderBy('date')->get(),
            'auth_activity_by_day' => SystemLog::query()->selectRaw('DATE(created_at) as date, action_type, count(*) as total')->where('created_at', '>=', now()->subDays(6)->startOfDay())->groupByRaw('DATE(created_at), action_type')->orderBy('date')->get(),
            'updated_at' => now()->toISOString(),
        ];
    }

    private function trainerSummaryPayload(Request $request): array
    {
        $trainerId = $request->user()->id;
        $memberRoleId = Role::query()->where('name', 'member')->value('id');
        $connectedMemberIds = TrainerBooking::query()
            ->where('trainer_id', $trainerId)
            ->whereIn('status', [TrainerBooking::STATUS_CONFIRMED, TrainerBooking::STATUS_COMPLETED])
            ->pluck('member_id')
            ->unique()
            ->values();
        $memberQuery = User::query()
            ->whereIn('id', $connectedMemberIds)
            ->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId));
        $memberIds = (clone $memberQuery)->pluck('id');
        $completedWeek = WorkoutTracking::query()
            ->whereIn('user_id', $memberIds)
            ->where('is_completed', true)
            ->where('logged_at', '>=', now()->startOfWeek())
            ->count();
        $paidIncome = (float) TrainerEarning::query()->where('trainer_id', $trainerId)->where('status', 'disbursed')->sum('trainer_amount');
        $pendingIncome = (float) TrainerEarning::query()->where('trainer_id', $trainerId)->where('status', 'pending')->sum('trainer_amount');

        return [
            'total_monitored_members' => (clone $memberQuery)->count(),
            'total_members' => (clone $memberQuery)->count(),
            'active_workout_plans' => WorkoutPlan::query()->whereIn('user_id', $memberIds)->where('completed', false)->count(),
            'completed_workouts' => WorkoutTracking::query()->whereIn('user_id', $memberIds)->where('is_completed', true)->count(),
            'completed_exercises_this_week' => $completedWeek,
            'nutrition_records' => NutritionCalculator::query()->whereIn('user_id', $memberIds)->count(),
            'recent_nutrition_records' => NutritionCalculator::query()->whereIn('user_id', $memberIds)->where('calculated_at', '>=', now()->subDays(7))->count(),
            'meal_plans' => MealPlan::query()->whereIn('user_id', $memberIds)->count(),
            'total_earnings' => $paidIncome + $pendingIncome,
            'total_paid_income' => $paidIncome,
            'pending_income' => $pendingIncome,
            'earnings_this_month' => (float) TrainerEarning::query()->where('trainer_id', $trainerId)->whereMonth('disbursed_at', now()->month)->whereYear('disbursed_at', now()->year)->sum('trainer_amount'),
            'recent_monitored_member_activity' => WorkoutTracking::query()->with('user:id,full_name,email')->whereIn('user_id', $memberIds)->latest('logged_at')->limit(8)->get(),
            'recent_trainer_payments' => TrainerEarning::query()->with(['payment.user', 'booking.member'])->where('trainer_id', $trainerId)->latest('id')->limit(8)->get(),
            'earnings_by_month' => TrainerEarning::query()
                ->selectRaw("TO_CHAR(COALESCE(disbursed_at, created_at), 'YYYY-MM') as month, sum(trainer_amount) as total")
                ->where('trainer_id', $trainerId)
                ->groupByRaw("TO_CHAR(COALESCE(disbursed_at, created_at), 'YYYY-MM')")
                ->orderBy('month')
                ->limit(6)
                ->get(),
            'workout_completion' => [
                ['status' => 'completed', 'total' => WorkoutTracking::query()->whereIn('user_id', $memberIds)->where('is_completed', true)->count()],
                ['status' => 'pending', 'total' => WorkoutTracking::query()->whereIn('user_id', $memberIds)->where('is_completed', false)->count()],
            ],
            'member_activity' => WorkoutTracking::query()->selectRaw('user_id, count(*) as total')->whereIn('user_id', $memberIds)->groupBy('user_id')->limit(8)->get(),
            'updated_at' => now()->toISOString(),
        ];
    }
}
