<?php

namespace App\Features\Trainer\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Trainer\Requests\IncomingRentHistoryRequest;
use App\Models\TrainerBooking;
use App\Models\TrainerEarning;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use App\Shared\Support\TrendCalculator;
use Illuminate\Http\Request;

class IncomingRentHistoryController extends Controller
{
    public function summary(Request $request)
    {
        $trainerId = $request->user()->id;

        // Stats dari TrainerEarning (history payout)
        $base = TrainerEarning::query()->where('trainer_id', $trainerId);

        $amountNow = (float) TrainerEarning::query()
            ->where('trainer_id', $trainerId)
            ->whereHas('payment', fn ($q) => $q->whereBetween('payment_date', [now()->subDays(7), now()]))
            ->sum('trainer_amount');
        $amountPrev = (float) TrainerEarning::query()
            ->where('trainer_id', $trainerId)
            ->whereHas('payment', fn ($q) => $q->whereBetween('payment_date', [now()->subDays(14), now()->subDays(7)]))
            ->sum('trainer_amount');
        $amountTrend = TrendCalculator::percentage($amountNow, $amountPrev);

        // Stats dari TrainerBooking confirmed (kontrak aktif)
        $bookingStats = TrainerBooking::query()
            ->where('trainer_id', $trainerId)
            ->where('status', TrainerBooking::STATUS_CONFIRMED)
            ->selectRaw('COUNT(*) as total_bookings')
            ->selectRaw('COALESCE(SUM(total_trainer_price), 0) as total_earnings')
            ->first();

        return ApiResponse::success('Incoming rent summary loaded.', [
            'total_bookings'      => (int) ($bookingStats->total_bookings ?? 0),
            'total_earnings'      => (float) ($bookingStats->total_earnings ?? 0),
            'total_records'       => (clone $base)->count(),
            'total_trainer_amount' => (float) (clone $base)->sum('trainer_amount'),
            'total_trainer_amount_trend' => $amountTrend,
            'pending_amount'      => (float) (clone $base)->where('status', 'pending')->sum('trainer_amount'),
            'disbursed_amount'    => (float) (clone $base)->where('status', 'disbursed')->sum('trainer_amount'),
            'this_month_amount'   => (float) (clone $base)
                ->whereMonth('disbursed_at', now()->month)
                ->whereYear('disbursed_at', now()->year)
                ->sum('trainer_amount'),
        ]);
    }

    public function index(IncomingRentHistoryRequest $request)
    {
        $data = $request->validated();
        $trainerId = $request->user()->id;
        $search = SearchTerm::contains($data['search'] ?? null);

        $earnings = TrainerEarning::query()
            ->with(['payment.user', 'booking.member', 'booking.trainer'])
            ->where('trainer_id', $trainerId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('status', 'ilike', $search)
                        ->orWhereHas('payment', function ($paymentQuery) use ($search) {
                            $paymentQuery->where('invoice_number', 'ilike', $search)
                                ->orWhere('payment_method', 'ilike', $search)
                                ->orWhere('payment_status', 'ilike', $search);
                        })
                        ->orWhereHas('booking.member', function ($memberQuery) use ($search) {
                            $memberQuery->where('full_name', 'ilike', $search)
                                ->orWhere('email', 'ilike', $search);
                        });
                });
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['start_date'] ?? null, fn ($query, $date) => $query->whereDate('disbursed_at', '>=', $date))
            ->when($data['end_date'] ?? null, fn ($query, $date) => $query->whereDate('disbursed_at', '<=', $date))
            ->orderByDesc('id')
            ->paginate($request->perPage(10));

        return ApiResponse::success('Incoming rent history loaded.', $earnings);
    }

    public function breakdown(Request $request)
    {
        $trainerId = $request->user()->id;

        $rows = TrainerEarning::query()
            ->where('trainer_id', $trainerId)
            ->join('trainer_bookings', 'trainer_bookings.id', '=', 'trainer_earnings.booking_id')
            ->selectRaw("
                0 as mentoring_income,
                SUM(trainer_earnings.trainer_amount) as session_income,
                SUM(trainer_earnings.trainer_amount) as total_income,
                COUNT(*) as total_entries
            ")
            ->first();

        $thisMonth = TrainerEarning::query()
            ->where('trainer_id', $trainerId)
            ->whereMonth('disbursed_at', now()->month)
            ->whereYear('disbursed_at', now()->year)
            ->sum('trainer_amount');

        return ApiResponse::success('Income breakdown loaded.', [
            'mentoring_income'   => (float) ($rows->mentoring_income ?? 0),
            'session_income'     => (float) ($rows->session_income ?? 0),
            'total_income'       => (float) ($rows->total_income ?? 0),
            'total_entries'      => (int) ($rows->total_entries ?? 0),
            'this_month_income'  => (float) $thisMonth,
        ]);
    }

}
