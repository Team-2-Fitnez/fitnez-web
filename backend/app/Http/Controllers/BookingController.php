<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Payment;
use App\Models\TrainerEarning;
use App\Models\TrainerBooking;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * List bookings for the authenticated user (member or trainer).
     */
    public function index(Request $request)
    {
        $bookings = TrainerBooking::query()
            ->with(['trainer:id,full_name,profile_picture_url', 'member:id,full_name'])
            ->forUser($request->user()->id)
            ->orderByDesc('booking_date')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success('Bookings loaded.', $bookings);
    }

    /**
     * Create a new booking and notify the trainer.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_id'   => 'required|integer|exists:users,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'session_type' => 'required|in:online,offline',
            'location'     => 'nullable|string|max:255',
            'member_notes' => 'nullable|string|max:1000',
            'total_price'  => 'nullable|numeric|min:0',
        ]);

        // Check for scheduling conflicts
        $conflict = TrainerBooking::query()
            ->where('trainer_id', $data['trainer_id'])
            ->where('booking_date', $data['booking_date'])
            ->whereNotIn('status', [TrainerBooking::STATUS_CANCELLED, TrainerBooking::STATUS_REJECTED])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->exists();

        if ($conflict) {
            return ApiResponse::error('Trainer sudah memiliki jadwal di waktu tersebut.', [], 422);
        }

        $booking = DB::transaction(function () use ($data, $request) {
            $booking = TrainerBooking::create([
                'member_id'    => $request->user()->id,
                'trainer_id'   => $data['trainer_id'],
                'booking_date' => $data['booking_date'],
                'start_time'   => $data['start_time'],
                'end_time'     => $data['end_time'],
                'session_type' => $data['session_type'],
                'location'     => $data['location'] ?? 'Gym Utama',
                'member_notes' => $data['member_notes'] ?? null,
                'total_price'  => $data['total_price'] ?? 0,
                'status'       => TrainerBooking::STATUS_PENDING,
            ]);

            $booking->load(['trainer:id,full_name', 'member:id,full_name']);

            // Notify the trainer about the new booking request
            $memberName = $booking->member?->full_name ?? 'Member';
            $date = $booking->booking_date?->format('d M Y') ?? '';

            Notification::create([
                'user_id'           => $booking->trainer_id,
                'title'             => 'Permintaan sesi baru',
                'body'              => "{$memberName} memesan sesi pada {$date} pukul {$booking->start_time}–{$booking->end_time}. Tinjau di Jadwal Melatih.",
                'notification_type' => 'booking_request',
                'is_read'           => false,
            ]);

            return $booking;
        });

        return ApiResponse::success('Booking created.', $booking, 201);
    }

    /**
     * Update the status of a booking (trainer confirms/rejects, etc.).
     */
    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:confirmed,completed,cancelled,rejected',
        ]);

        $booking = TrainerBooking::with(['member:id,full_name', 'trainer:id,full_name'])->find($id);

        if (! $booking) {
            return ApiResponse::error('Booking not found.', [], 404);
        }

        // Only the trainer or the member involved can update status
        $userId = $request->user()->id;
        if ($booking->trainer_id !== $userId && $booking->member_id !== $userId) {
            return ApiResponse::error('Not authorized to update this booking.', [], 403);
        }

        $previousStatus = $booking->status;
        $booking->update(['status' => $data['status']]);

        // Notify trainer when booking is confirmed (payment)
        if (
            $data['status'] === TrainerBooking::STATUS_CONFIRMED
            && $previousStatus !== TrainerBooking::STATUS_CONFIRMED
        ) {
            $memberName = $booking->member?->full_name ?? 'Member';
            $date = $booking->booking_date?->format('d M Y') ?? '';
            $price = number_format((int) $booking->total_price, 0, ',', '.');

            Notification::create([
                'user_id'           => $booking->trainer_id,
                'title'             => 'Sesi dikonfirmasi & pembayaran',
                'body'              => "Sesi dengan {$memberName} pada {$date} telah dikonfirmasi. Total pembayaran Rp {$price}.",
                'notification_type' => 'payment_in',
                'is_read'           => false,
            ]);

            $payment = Payment::query()->firstOrCreate(
                ['booking_id' => $booking->id, 'payment_type' => 'trainer_booking'],
                [
                    'invoice_number' => 'FITNEZ-TRN-'.$booking->id.'-'.now()->format('YmdHis'),
                    'user_id' => $booking->member_id,
                    'amount' => $booking->total_price,
                    'payment_method' => 'manual',
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'type' => 'trainer_booking',
                    'payment_date' => null,
                ]
            );

            TrainerEarning::query()->firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'trainer_id' => $booking->trainer_id,
                    'member_id' => $booking->member_id,
                    'payment_id' => $payment->id,
                    'commission_rate' => 100,
                    'trainer_amount' => $booking->total_price,
                    'amount' => $booking->total_price,
                    'status' => 'pending',
                    'earned_at' => now()->toDateString(),
                    'description' => 'Trainer booking session earning',
                ]
            );
        }

        if (
            $data['status'] === TrainerBooking::STATUS_COMPLETED
            && $previousStatus !== TrainerBooking::STATUS_COMPLETED
        ) {
            $payment = Payment::query()
                ->where('booking_id', $booking->id)
                ->where('payment_type', 'trainer_booking')
                ->first();

            if ($payment) {
                $payment->update([
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'payment_date' => now(),
                    'paid_at' => now(),
                ]);
            }

            TrainerEarning::query()
                ->where('booking_id', $booking->id)
                ->update([
                    'status' => 'disbursed',
                    'disbursed_at' => now(),
                    'paid_at' => now()->toDateString(),
                ]);
        }

        return ApiResponse::success('Booking status updated.', $booking->fresh());
    }
}
