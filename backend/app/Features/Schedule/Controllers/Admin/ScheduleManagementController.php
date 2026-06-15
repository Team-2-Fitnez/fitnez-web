<?php

namespace App\Features\Schedule\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Shared\Requests\Admin\IndexTableRequest;
use App\Shared\Requests\Admin\StoreScheduleRequest;
use App\Shared\Requests\Admin\UpdateScheduleRequest;
use App\Models\TrainerBooking;
use App\Support\ApiResponse;
use App\Support\RequiresConfirmation;
use App\Support\SearchTerm;
use Illuminate\Http\Request;

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
                    $q->where('member_notes', 'ilike', $search)
                        ->orWhere('session_time', 'ilike', $search)
                        ->orWhereHas('member', fn ($memberQuery) => $memberQuery->where('full_name', 'ilike', $search))
                        ->orWhereHas('trainer', fn ($trainerQuery) => $trainerQuery->where('full_name', 'ilike', $search));
                });
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ApiResponse::success('Schedules loaded.', $schedules);
    }

    public function store(StoreScheduleRequest $request)
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? TrainerBooking::STATUS_PENDING;
        $data['total_sessions'] = $data['total_sessions'] ?? (($data['sessions_per_week'] ?? 0) * 4);

        $schedule = TrainerBooking::query()->create($data);

        return ApiResponse::success('Schedule created.', $schedule->load(['member', 'trainer']), 201);
    }

    public function update(UpdateScheduleRequest $request, TrainerBooking $schedule)
    {
        $schedule->update($request->validated());

        return ApiResponse::success('Schedule updated.', $schedule->fresh(['member', 'trainer']));
    }

    public function destroy(Request $request, TrainerBooking $schedule)
    {
        if ($confirmation = RequiresConfirmation::check($request, 'delete_schedule', 'schedule #'.$schedule->id)) {
            return $confirmation;
        }

        $schedule->delete();

        return ApiResponse::success('Schedule deleted.');
    }
}
