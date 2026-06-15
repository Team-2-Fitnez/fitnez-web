<?php

namespace App\Features\Reports\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Shared\Requests\Admin\MemberPaymentAttendanceReportRequest;
use App\Models\Attendance;
use App\Models\MealPlan;
use App\Models\Payment;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use App\Shared\Support\TrendCalculator;
use Illuminate\Http\Request;

class MemberPaymentAttendanceReportController extends Controller
{
    public function summary(Request $request)
    {
        $amountNow = (float) Payment::query()->where('payment_status', 'paid')->whereBetween('payment_date', [now()->subDays(7), now()])->sum('amount');
        $amountPrev = (float) Payment::query()->where('payment_status', 'paid')->whereBetween('payment_date', [now()->subDays(14), now()->subDays(7)])->sum('amount');
        $amountTrend = TrendCalculator::percentage($amountNow, $amountPrev);

        $paymentsNow = Payment::query()->where('payment_status', 'paid')->whereBetween('payment_date', [now()->subDays(7), now()])->count();
        $paymentsPrev = Payment::query()->where('payment_status', 'paid')->whereBetween('payment_date', [now()->subDays(14), now()->subDays(7)])->count();
        $paymentsTrend = TrendCalculator::percentage($paymentsNow, $paymentsPrev);

        $attendanceToday = Attendance::query()->whereDate('check_in_time', now()->toDateString())->count();
        $attendanceYesterday = Attendance::query()->whereDate('check_in_time', now()->subDay()->toDateString())->count();
        $attendanceTrend = TrendCalculator::percentage($attendanceToday, $attendanceYesterday);

        return ApiResponse::success('Member payment and attendance summary loaded.', [
            'total_payments' => Payment::query()->count(),
            'total_payments_trend' => $paymentsTrend,
            'total_payment_amount' => (float) Payment::query()->sum('amount'),
            'total_payment_amount_trend' => $amountTrend,
            'paid_payments' => Payment::query()->where('payment_status', 'paid')->count(),
            'pending_payments' => Payment::query()->where('payment_status', 'pending')->count(),
            'total_attendance' => Attendance::query()->count(),
            'attendance_today' => $attendanceToday,
            'attendance_today_trend' => $attendanceTrend,
            'attendance_this_month' => Attendance::query()
                ->whereMonth('check_in_time', now()->month)
                ->whereYear('check_in_time', now()->year)
                ->count(),
        ]);
    }

    public function payments(MemberPaymentAttendanceReportRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $payments = Payment::query()
            ->with(['user.role', 'user.membershipPackage', 'booking.member', 'booking.trainer'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'ilike', $search)
                        ->orWhere('payment_type', 'ilike', $search)
                        ->orWhere('payment_method', 'ilike', $search)
                        ->orWhere('payment_status', 'ilike', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('full_name', 'ilike', $search)
                                ->orWhere('email', 'ilike', $search)
                                ->orWhere('phone', 'ilike', $search);
                        });
                });
            })
            ->when($data['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($data['payment_type'] ?? null, fn ($query, $type) => $query->where('payment_type', $type))
            ->when($data['start_date'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '>=', $date))
            ->when($data['end_date'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '<=', $date))
            ->orderByDesc('id')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Member payment report loaded.', $payments);
    }

    public function attendance(MemberPaymentAttendanceReportRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $attendance = Attendance::query()
            ->with(['user.role', 'booking.member', 'booking.trainer'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('attendance_type', 'ilike', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('full_name', 'ilike', $search)
                                ->orWhere('email', 'ilike', $search)
                                ->orWhere('phone', 'ilike', $search);
                        })
                        ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                            $bookingQuery->where('member_notes', 'ilike', $search)
                                ->orWhere('session_time', 'ilike', $search);
                        });
                });
            })
            ->when($data['attendance_type'] ?? null, fn ($query, $type) => $query->where('attendance_type', $type))
            ->when($data['start_date'] ?? null, fn ($query, $date) => $query->whereDate('check_in_time', '>=', $date))
            ->when($data['end_date'] ?? null, fn ($query, $date) => $query->whereDate('check_in_time', '<=', $date))
            ->orderByDesc('check_in_time')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Member attendance report loaded.', $attendance);
    }

    public function nutrition(MemberPaymentAttendanceReportRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $plans = MealPlan::query()
            ->with(['user.role', 'meals'])
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('full_name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search)
                        ->orWhere('phone', 'ilike', $search);
                });
            })
            ->orderByDesc('id')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Nutrition monitoring data loaded.', $plans);
    }
}
