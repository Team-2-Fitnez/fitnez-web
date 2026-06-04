<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberPaymentAttendanceReportRequest;
use App\Models\Attendance;
use App\Models\Payment;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Http\Request;

class MemberPaymentAttendanceReportController extends Controller
{
    public function summary(Request $request)
    {
        $totalPayments = Payment::query()->count();
        $totalPaymentAmount = (float) Payment::query()->sum('amount');
        $paidPayments = Payment::query()->where('payment_status', 'paid')->count();
        $pendingPayments = Payment::query()->where('payment_status', 'pending')->count();
        $totalAttendance = Attendance::query()->count();
        $attendanceToday = Attendance::query()->whereDate('check_in_time', now()->toDateString())->count();

        return ApiResponse::success('Member payment and attendance summary loaded.', [
            'total_payments' => $totalPayments,
            'total_member_payments' => $totalPayments,
            'total_payment_amount' => $totalPaymentAmount,
            'paid_payments' => $paidPayments,
            'paid_member_payments' => $paidPayments,
            'pending_payments' => $pendingPayments,
            'pending_member_payments' => $pendingPayments,
            'total_attendance' => $totalAttendance,
            'total_attendance_records' => $totalAttendance,
            'attendance_today' => $attendanceToday,
            'today_attendance_count' => $attendanceToday,
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
            ->with(['user.role', 'booking.member', 'booking.trainer'])
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
                            $bookingQuery->where('session_type', 'ilike', $search)
                                ->orWhere('location', 'ilike', $search);
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
}
