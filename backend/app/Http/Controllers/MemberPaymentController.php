<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\Payment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MemberPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::query()
            ->where('user_id', $request->user()->id)
            ->with('booking')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::success('Payment history loaded.', $payments);
    }

    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        $payments = Payment::where('user_id', $userId);

        return ApiResponse::success('Payment summary loaded.', [
            'total_payments' => (clone $payments)->count(),
            'total_amount' => (clone $payments)->sum('amount'),
            'paid_count' => (clone $payments)->where('payment_status', 'paid')->count(),
            'pending_count' => (clone $payments)->where('payment_status', 'pending')->count(),
        ]);
    }

    public function simulateCreate(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1000',
        ]);

        $payment = Payment::create([
            'invoice_number' => 'INV-DEMO-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'user_id' => $request->user()->id,
            'payment_type' => 'demo_membership',
            'amount' => $data['amount'],
            'payment_method' => 'demo',
            'payment_status' => 'pending',
            'payment_date' => now(),
        ]);

        $notif = Notification::create([
            'user_id' => $payment->user_id,
            'title' => 'New Invoice',
            'body' => 'Invoice ' . $payment->invoice_number . ' for Rp ' . number_format($payment->amount, 0, ',', '.') . ' has been created.',
            'notification_type' => 'payment_in',
            'is_read' => false,
        ]);
        event(new NewNotification($notif));

        return ApiResponse::success('Demo payment created.', $payment);
    }

    public function simulatePay(Request $request)
    {
        $data = $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
        ]);

        $payment = Payment::query()
            ->where('id', $data['payment_id'])
            ->where('user_id', $request->user()->id)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        sleep(1);

        $payment->update([
            'payment_method' => 'demo_simulation',
            'external_reference' => 'DEMO-TRX-' . strtoupper(\Illuminate\Support\Str::random(12)),
            'payment_date' => now(),
            'payment_status' => 'paid',
        ]);

        $notif = Notification::create([
            'user_id' => $payment->user_id,
            'title' => 'Payment Successful',
            'body' => 'Payment ' . $payment->invoice_number . ' amount Rp ' . number_format($payment->amount, 0, ',', '.') . ' has been confirmed.',
            'notification_type' => 'payment_in',
            'is_read' => false,
        ]);
        event(new NewNotification($notif));

        return ApiResponse::success('Demo payment successful! (Simulation)', $payment);
    }
}
