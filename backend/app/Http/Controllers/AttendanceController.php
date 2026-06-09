<?php

namespace App\Http\Controllers;

use App\Events\AttendanceChecked;
use App\Models\Attendance;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'nullable|integer|exists:trainer_bookings,id',
            'attendance_type' => 'nullable|string|in:member_checkin,trainer_checkin,class_attendance',
        ]);

        $existing = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('check_out_time')
            ->latest('check_in_time')
            ->first();

        if ($existing) {
            return ApiResponse::error('You have already checked in. Please check out first.', [], 422);
        }

        $attendance = Attendance::create([
            'user_id' => $request->user()->id,
            'check_in_time' => now(),
            'attendance_type' => $data['attendance_type'] ?? 'member_checkin',
            'booking_id' => $data['booking_id'] ?? null,
        ]);

        AttendanceChecked::dispatch($attendance, 'checkin');

        return ApiResponse::success('Check-in successful.', $attendance, 201);
    }

    public function checkOut(Request $request)
    {
        $attendance = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('check_out_time')
            ->latest('check_in_time')
            ->first();

        if (!$attendance) {
            return ApiResponse::error('No active check-in found.', [], 404);
        }

        $attendance->update(['check_out_time' => now()]);

        AttendanceChecked::dispatch($attendance, 'checkout');

        return ApiResponse::success('Check-out successful.', $attendance);
    }

    public function myHistory(Request $request)
    {
        $attendances = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->with('booking')
            ->orderByDesc('check_in_time')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::success('Attendance history loaded.', $attendances);
    }
}
