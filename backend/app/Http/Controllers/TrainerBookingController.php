<?php

namespace App\Http\Controllers;

use App\Models\TrainerBooking;
use App\Models\Notification;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TrainerBookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'session_type' => 'required|string',
            'member_notes' => 'nullable|string',
        ]);

        $booking = TrainerBooking::create([
            'member_id' => $request->user()->id,
            'trainer_id' => $validated['trainer_id'],
            'booking_date' => now()->toDateString(),
            'start_time' => now()->toTimeString(),
            'end_time' => now()->addHour()->toTimeString(),
            'session_type' => $validated['session_type'],
            'status' => 'pending',
            'total_price' => 0, // Simplified for now
        ]);


        Notification::create([
            'user_id' => $validated['trainer_id'],
            'title' => 'Anda Disewa Member!',
            'body' => "Member \"{$request->user()->full_name}\" telah menyewa Anda untuk sesi {$validated['session_type']}.",
            'notification_type' => 'hire',
            'is_read' => false,
        ]);

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
