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

        $conflict = TrainerBooking::query()
            ->where('trainer_id', $data['trainer_id'])
            ->whereNotIn('status', [TrainerBooking::STATUS_CANCELLED])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->exists();

        if ($conflict) {
            return ApiResponse::error('Trainer already has an active booking in this period.', [], 422);
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
            'title'             => 'Bukti Pembayaran Terkirim',
            'body'              => "Bukti pembayaran untuk booking dengan {$booking->trainer->full_name} telah dikirim. Admin akan memverifikasi dalam 2x24 jam.",
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

        if ($data['status'] === TrainerBooking::STATUS_COMPLETED) {
            Notification::create([
                'user_id'           => $booking->member_id,
                'title'             => 'Sesi Selesai',
                'body'              => "Booking dengan {$booking->trainer->full_name} telah selesai. Terima kasih!",
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
                'title'             => 'Booking Dibatalkan',
                'body'              => "Booking periode {$booking->start_date} – {$booking->end_date} telah dibatalkan oleh {$actorName}.",
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
            'title'             => 'Pembayaran Dikonfirmasi',
            'body'              => "Pembayaran Anda telah dikonfirmasi. Chat dengan {$booking->trainer->full_name} sekarang sudah terbuka!",
            'notification_type' => 'booking_confirmed',
            'is_read'           => false,
        ]);

        Notification::create([
            'user_id'           => $booking->trainer_id,
            'title'             => 'Sesi Baru Aktif',
            'body'              => "{$booking->member->full_name} telah aktif. Anda menerima Rp " . number_format($booking->total_trainer_price, 0, ',', '.') . " untuk bulan ini.",
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
            'title'             => 'Pembayaran Ditolak',
            'body'              => "Pembayaran untuk booking dengan {$booking->trainer->full_name} ditolak. Alasan: " . ($data['reason'] ?? 'Bukti transfer tidak valid. Silakan upload ulang.'),
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
