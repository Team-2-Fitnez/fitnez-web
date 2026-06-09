<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\IncomingRentHistoryRequest;
use App\Models\TrainerEarning;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Http\Request;

class IncomingRentHistoryController extends Controller
{
    public function summary(Request $request)
    {
        $trainerId = $request->user()->id;
        $base = TrainerEarning::query()->where('trainer_id', $trainerId);

        $amountNow = (float) TrainerEarning::query()
            ->where('trainer_id', $trainerId)
            ->whereHas('payment', fn ($q) => $q->whereBetween('payment_date', [now()->subDays(7), now()]))
            ->sum('trainer_amount');
        $amountPrev = (float) TrainerEarning::query()
            ->where('trainer_id', $trainerId)
            ->whereHas('payment', fn ($q) => $q->whereBetween('payment_date', [now()->subDays(14), now()->subDays(7)]))
            ->sum('trainer_amount');
        $amountTrend = $this->calculateTrend($amountNow, $amountPrev);

        return ApiResponse::success('Incoming rent summary loaded.', [
            'total_records' => (clone $base)->count(),
            'total_trainer_amount' => (float) (clone $base)->sum('trainer_amount'),
            'total_trainer_amount_trend' => $amountTrend,
            'pending_amount' => (float) (clone $base)->where('status', 'pending')->sum('trainer_amount'),
            'disbursed_amount' => (float) (clone $base)->where('status', 'disbursed')->sum('trainer_amount'),
            'this_month_amount' => (float) (clone $base)
                ->whereMonth('disbursed_at', now()->month)
                ->whereYear('disbursed_at', now()->year)
                ->sum('trainer_amount'),
        ]);
    }

    private function calculateTrend(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
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
}
