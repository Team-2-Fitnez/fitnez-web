<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Payment;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
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

    private function calculateTrend(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function summaryPayload(): array
    {
        $weekly = [];
        $startOfWeek = now()->startOfWeek();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i)->toDateString();
            $weekly[] = User::query()->whereDate('created_at', $date)->count();
        }

        $monthly = [];
        for ($i = 6; $i >= 0; $i--) {
            $start = now()->subDays(($i + 1) * 4)->toDateTimeString();
            $end = now()->subDays($i * 4)->toDateTimeString();
            $monthly[] = User::query()->whereBetween('created_at', [$start, $end])->count();
        }

        $usersTotal = User::query()->count();
        $usersPrev = User::query()->where('created_at', '<=', now()->subDays(7))->count();
        $usersTrend = $this->calculateTrend($usersTotal, $usersPrev);

        $trainersTotal = TrainerDetail::query()->count();
        $trainersPrev = User::query()->whereHas('role', fn ($q) => $q->where('name', 'trainer'))->where('created_at', '<=', now()->subDays(7))->count();
        $trainersTrend = $this->calculateTrend($trainersTotal, $trainersPrev);

        return [
            'users_total' => $usersTotal,
            'users_total_trend' => $usersTrend,
            'new_registrations_today' => User::query()->whereDate('created_at', today())->count(),
            'trainers_total' => $trainersTotal,
            'trainers_total_trend' => $trainersTrend,
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
            'weekly_activations' => $weekly,
            'monthly_activations' => $monthly,
            'updated_at' => now()->toISOString(),
        ];
    }
}
