<?php

namespace App\Features\Booking\Controllers;

use App\Http\Controllers\Controller;

use App\Events\NewNotification;
use App\Models\TrainerBooking;
use App\Models\Notification;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @deprecated Use BookingController instead. This controller is a simplified
 *             draft kept for reference. Frontend uses BookingController only.
 */
class TrainerBookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date|after_or_equal:today',
            'sessions_per_week' => 'nullable|integer|in:3,5,7',
            'session_days' => 'nullable|array|min:1|max:7',
            'session_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'session_time' => 'nullable|date_format:H:i',
            'member_notes' => 'nullable|string',
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date'] ?? now()->toDateString());
        $sessionsPerWeek = (int) ($validated['sessions_per_week'] ?? 3);
        $totalSessions = $sessionsPerWeek * 4;

        $booking = TrainerBooking::create([
            'member_id' => $request->user()->id,
            'trainer_id' => $validated['trainer_id'],
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addWeeks(4)->subDay()->toDateString(),
            'sessions_per_week' => $sessionsPerWeek,
            'session_days' => $validated['session_days'] ?? ['monday', 'wednesday', 'friday'],
            'session_time' => $validated['session_time'] ?? now()->format('H:i'),
            'member_notes' => $validated['member_notes'] ?? null,
            'status' => TrainerBooking::STATUS_PENDING,
            'base_price_per_session' => 0,
            'member_price_per_session' => 0,
            'total_member_price' => 0,
            'total_trainer_price' => 0,
            'total_sessions' => $totalSessions,
        ]);

        $notif = Notification::create([
            'user_id' => $validated['trainer_id'],
            'title' => 'A Member Hired You!',
            'body' => "Member \"{$request->user()->full_name}\" requested a trainer booking.",
            'notification_type' => 'hire',
            'is_read' => false,
        ]);

        try {
            broadcast(new NewNotification($notif));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast NewNotification failed: ' . $e->getMessage());
        }

        return ApiResponse::success('Trainer hired successfully.', $booking, 201);
    }

    public function getTrainers(): JsonResponse
    {

        $trainers = User::whereHas('role', function($q) {
            $q->where('name', 'trainer');
        })->get();

        return ApiResponse::success('Trainers loaded.', $trainers);
    }
}
