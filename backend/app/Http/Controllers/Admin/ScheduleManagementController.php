<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\TrainerBooking;
use App\Support\ApiResponse;
use App\Support\SearchTerm;

class ScheduleManagementController extends Controller
{
    public function index(IndexTableRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $schedules = TrainerBooking::query()
            ->with(['member.role', 'trainer.role'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('session_type', 'ilike', $search)
                        ->orWhere('location', 'ilike', $search)
                        ->orWhereHas('member', fn ($memberQuery) => $memberQuery->where('full_name', 'ilike', $search))
                        ->orWhereHas('trainer', fn ($trainerQuery) => $trainerQuery->where('full_name', 'ilike', $search));
                });
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ApiResponse::success('Schedules loaded.', $schedules);
    }

    public function store(StoreScheduleRequest $request)
    {
        $schedule = TrainerBooking::query()->create([
            ...$request->validated(),
            'status' => $request->validated('status') ?? 'scheduled',
        ]);

        return ApiResponse::success('Schedule created.', $schedule->load(['member', 'trainer']), 201);
    }

    public function update(UpdateScheduleRequest $request, TrainerBooking $schedule)
    {
        $schedule->update($request->validated());

        return ApiResponse::success('Schedule updated.', $schedule->fresh(['member', 'trainer']));
    }

    public function destroy(TrainerBooking $schedule)
    {
        $schedule->delete();

        return ApiResponse::success('Schedule deleted.');
    }
}
