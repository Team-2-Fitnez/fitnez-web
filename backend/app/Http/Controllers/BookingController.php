<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\TrainerEarning;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = TrainerBooking::query()
            ->with(['trainer:id,full_name,profile_picture_url', 'member:id,full_name'])
            ->forUser($request->user()->id)
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success('Bookings loaded.', $bookings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_id'              => 'required|integer|exists:users,id',
            'start_date'              => 'required|date|after_or_equal:today',
            'sessions_per_week'       => 'required|integer|in:3,5,7',
            'session_days'            => 'required|array|size:' . $request->input('sessions_per_week'),
            'session_days.*'          => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'session_time'            => 'required|date_format:H:i',
            'member_notes'            => 'nullable|string|max:1000',
        ]);

        $trainerDetail = TrainerDetail::where('user_id', $data['trainer_id'])->firstOrFail();

        if (($trainerDetail->base_price ?? 0) <= 0) {
            return ApiResponse::error('Trainer pricing not configured. Contact admin.', [], 422);
        }

        $basePrice = (float) $trainerDetail->base_price;
        $memberPrice = $basePrice * 1.5;

        $start = Carbon::parse($data['start_date']);
        $end = $start->copy()->addWeeks(4)->subDay();

        $overlappingBookings = TrainerBooking::query()
            ->where('trainer_id', $data['trainer_id'])
            ->whereNotIn('status', [TrainerBooking::STATUS_CANCELLED, TrainerBooking::STATUS_COMPLETED])
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get();

        $conflict = false;
        $newDays = array_map('strtolower', $data['session_days']);
        $newTime = Carbon::parse($data['session_time'])->format('H:i');

        foreach ($overlappingBookings as $existingBooking) {
            $existingDays = array_map('strtolower', $existingBooking->session_days ?? []);
            $commonDays = array_intersect($newDays, $existingDays);
            if (!empty($commonDays)) {
                $existingTime = Carbon::parse($existingBooking->session_time)->format('H:i');
                if ($newTime === $existingTime) {
                    $conflict = true;
                    break;
                }
            }
        }

        if ($conflict) {
            return ApiResponse::error('Trainer already has an active booking on these days at the same time.', [], 422);
        }

        $totalSessions = $data['sessions_per_week'] * 4;
        $totalMemberPrice = $totalSessions * $memberPrice;
        $totalTrainerPrice = $totalSessions * $basePrice;

        $booking = DB::transaction(function () use ($data, $start, $end, $totalSessions, $basePrice, $memberPrice, $totalMemberPrice, $totalTrainerPrice, $request) {
            $booking = TrainerBooking::create([
                'member_id'                => $request->user()->id,
                'trainer_id'               => $data['trainer_id'],
                'start_date'               => $start,
                'end_date'                 => $end,
                'sessions_per_week'        => $data['sessions_per_week'],
                'session_days'             => $data['session_days'],
                'session_time'             => $data['session_time'],
                'member_notes'             => $data['member_notes'] ?? null,
                'base_price_per_session'   => $basePrice,
                'member_price_per_session' => $memberPrice,
                'total_member_price'       => $totalMemberPrice,
                'total_trainer_price'      => $totalTrainerPrice,
                'total_sessions'           => $totalSessions,
                'status'                   => TrainerBooking::STATUS_PENDING,
            ]);

            $booking->load(['trainer:id,full_name', 'member:id,full_name']);

            Notification::create([
                'user_id'           => $booking->trainer_id,
                'title'             => 'New Booking Request',
                'body'              => "{$booking->member->full_name} has requested a booking with you.",
                'notification_type' => 'booking_request',
                'is_read'           => false,
            ]);

            return $booking;
        });

        return ApiResponse::success('Booking created. Please upload payment proof to proceed.', $booking, 201);
    }

    public function uploadPaymentProof(Request $request, TrainerBooking $booking)
    {
        $userId = $request->user()->id;
        if ($booking->member_id !== $userId) {
            return ApiResponse::error('Not authorized.', [], 403);
        }

        if ($booking->status !== TrainerBooking::STATUS_PENDING) {
            return ApiResponse::error(
                "Cannot upload proof for booking with status '{$booking->status}'.",
                [],
                422
            );
        }

        $data = $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        $booking->update([
            'payment_proof_path' => $path,
            'status'             => TrainerBooking::STATUS_PENDING_PAYMENT,
        ]);

        $booking->load(['trainer:id,full_name']);

        Notification::create([
            'user_id'           => $booking->member_id,
            'title'             => 'Payment Proof Submitted',
            'body'              => "Payment proof for your booking with {$booking->trainer->full_name} has been submitted. Admin will verify it within 2x24 hours.",
            'notification_type' => 'payment_reminder',
            'is_read'           => false,
        ]);

        return ApiResponse::success('Payment proof uploaded. Awaiting admin confirmation.', $booking->fresh());
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:completed,cancelled',
        ]);

        $booking = TrainerBooking::with(['member:id,full_name', 'trainer:id,full_name'])->findOrFail($id);
        $userId = $request->user()->id;
        $isAdmin = $request->user()->roleName() === 'admin';

        if ($booking->member_id !== $userId && $booking->trainer_id !== $userId && !$isAdmin) {
            return ApiResponse::error('Not authorized.', [], 403);
        }

        if (!$booking->canTransitionTo($data['status'])) {
            return ApiResponse::error(
                "Cannot change from '{$booking->status}' to '{$data['status']}'.",
                [],
                422
            );
        }

        $booking->update(['status' => $data['status']]);

        // If booking is now completed, mark associated trainer earning as paid
        if ($data['status'] === TrainerBooking::STATUS_COMPLETED) {
            TrainerEarning::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid',
                    'disbursed_at' => now(),
                ]);
        }

        if ($data['status'] === TrainerBooking::STATUS_COMPLETED) {
            Notification::create([
                'user_id'           => $booking->member_id,
                'title'             => 'Session Completed',
                'body'              => "Your booking with {$booking->trainer->full_name} has been completed. Thank you!",
                'notification_type' => 'booking_completed',
                'is_read'           => false,
            ]);
        }

        if ($data['status'] === TrainerBooking::STATUS_CANCELLED) {
            $otherUserId = $userId === $booking->member_id ? $booking->trainer_id : $booking->member_id;
            $actorName = $userId === $booking->member_id
                ? ($booking->member->full_name ?? 'Member')
                : ($booking->trainer->full_name ?? 'Trainer');

            Notification::create([
                'user_id'           => $otherUserId,
                'title'             => 'Booking Cancelled',
                'body'              => "The booking period {$booking->start_date} – {$booking->end_date} was cancelled by {$actorName}.",
                'notification_type' => 'booking_cancelled',
                'is_read'           => false,
            ]);
        }

        return ApiResponse::success('Booking status updated.', $booking->fresh());
    }

    public function confirmPayment(Request $request, TrainerBooking $booking)
    {
        if (!$booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED)) {
            return ApiResponse::error(
                "Cannot confirm payment for booking with status '{$booking->status}'.",
                [],
                422
            );
        }

        $booking->load(['member:id,full_name', 'trainer:id,full_name']);
        $booking->update([
            'status'  => TrainerBooking::STATUS_CONFIRMED,
            'paid_at' => now(),
        ]);

        TrainerEarning::create([
            'trainer_id'     => $booking->trainer_id,
            'booking_id'     => $booking->id,
            'trainer_amount' => $booking->total_trainer_price,
            'status'         => 'pending',
        ]);

        Notification::create([
            'user_id'           => $booking->member_id,
            'title'             => 'Payment Confirmed',
            'body'              => "Your payment has been confirmed. Chat with {$booking->trainer->full_name} is now available!",
            'notification_type' => 'booking_confirmed',
            'is_read'           => false,
        ]);

        Notification::create([
            'user_id'           => $booking->trainer_id,
            'title'             => 'New Active Session',
            'body'              => "{$booking->member->full_name} is now active. You receive Rp " . number_format($booking->total_trainer_price, 0, ',', '.') . " for this month.",
            'notification_type' => 'payment_in',
            'is_read'           => false,
        ]);

        try {
            broadcast(new NewNotification(
                Notification::where('user_id', $booking->member_id)
                    ->where('notification_type', 'booking_confirmed')
                    ->latest()
                    ->first()
            ));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast NewNotification failed: ' . $e->getMessage());
        }

        return ApiResponse::success('Payment confirmed. Chat is now open.', $booking->fresh());
    }

    /**
     * Auto-complete bookings that have passed end_date + 24h without trainer confirming.
     * Intended to be called by a scheduled job.
     */
    public function autoCompleteExpiredBookings()
    {
        $now = now();
        $threshold = $now->copy()->subDay(); // bookings whose end_date is older than 24h ago
        $bookings = TrainerBooking::query()
            ->where('status', TrainerBooking::STATUS_CONFIRMED)
            ->where('end_date', '<', $threshold)
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->canTransitionTo(TrainerBooking::STATUS_COMPLETED)) {
                $booking->update(['status' => TrainerBooking::STATUS_COMPLETED]);

                // Update related earning to paid
                TrainerEarning::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'paid',
                        'disbursed_at' => $now,
                    ]);

                // Notify trainer about earnings disbursement
                Notification::create([
                    'user_id' => $booking->trainer_id,
                    'title' => 'Earnings Disbursed',
                    'body' => "Your earnings for the completed booking with {$booking->member->full_name} have been transferred.",
                    'notification_type' => 'earning_paid',
                    'is_read' => false,
                ]);
            }
        }

        return ApiResponse::success('Auto-completion run completed.', ['processed' => $bookings->count()]);
    }

    public function pendingPayments(Request $request)
    {
        $bookings = TrainerBooking::query()
            ->with(['trainer:id,full_name', 'member:id,full_name'])
            ->pendingPayment()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success('Pending payments loaded.', $bookings);
    }

    public function rejectPayment(Request $request, TrainerBooking $booking)
    {
        if ($booking->status !== TrainerBooking::STATUS_PENDING_PAYMENT) {
            return ApiResponse::error(
                "Cannot reject booking with status '{$booking->status}'.",
                [],
                422
            );
        }

        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        $booking->load(['member:id,full_name', 'trainer:id,full_name']);

        $booking->update(['status' => TrainerBooking::STATUS_CANCELLED]);

        Notification::create([
            'user_id'           => $booking->member_id,
            'title'             => 'Payment Rejected',
            'body'              => "Payment for your booking with {$booking->trainer->full_name} was rejected. Reason: " . ($data['reason'] ?? 'The transfer proof is invalid. Please upload it again.'),
            'notification_type' => 'payment_rejected',
            'is_read'           => false,
        ]);

        return ApiResponse::success('Booking payment rejected.', $booking->fresh());
    }

    public function sessionDates(TrainerBooking $booking)
    {
        $userId = request()->user()->id;
        if ($booking->member_id !== $userId && $booking->trainer_id !== $userId) {
            return ApiResponse::error('Not authorized.', [], 403);
        }

        return ApiResponse::success('Session dates generated.', [
            'dates' => $booking->generateSessionDates(),
            'session_time' => $booking->session_time,
        ]);
    }
}
