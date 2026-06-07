<?php

namespace App\Http\Controllers;

use App\Models\WorkoutPlan;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workouts = WorkoutPlan::where('user_id', $request->user()->id)
            ->orderBy('date')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success('Workout plans loaded.', $workouts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'date'     => 'required|date',
            'day'      => 'nullable|string|max:20',
            'set'      => 'required|integer|min:1',
            'weight'   => 'required|numeric|min:0',
            'reps'     => 'required|integer|min:1',
            'duration' => 'nullable|integer|min:0',
        ]);

        $workout = WorkoutPlan::create([
            ...$validated,
            'user_id'   => $request->user()->id,
            'completed' => false,
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'title'   => 'Jadwal Baru Ditambahkan',
            'body'    => "Latihan \"{$workout->name}\" telah ditambahkan untuk tanggal " . \Carbon\Carbon::parse($workout->date)->format('d-m-Y') . ".",
            'notification_type' => 'workout_plan',
            'is_read' => false,
        ]);

        return ApiResponse::success('Workout plan created.', $workout, 201);
    }

    public function update(Request $request, WorkoutPlan $workout_plan): JsonResponse
    {
        if ($workout_plan->user_id !== $request->user()->id) {
            return ApiResponse::error('Unauthorized.', [], 403);
        }

        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'category'  => 'sometimes|required|string|max:100',
            'date'      => 'sometimes|required|date',
            'day'       => 'nullable|string|max:20',
            'set'       => 'sometimes|required|integer|min:1',
            'weight'    => 'sometimes|required|numeric|min:0',
            'reps'      => 'sometimes|required|integer|min:1',
            'duration'  => 'nullable|integer|min:0',
            'completed' => 'sometimes|boolean',
        ]);

        $workout_plan->update($validated);

        return ApiResponse::success('Workout plan updated.', $workout_plan->fresh());
    }

    public function destroy(Request $request, WorkoutPlan $workout_plan): JsonResponse
    {
        if ($workout_plan->user_id !== $request->user()->id) {
            return ApiResponse::error('Unauthorized.', [], 403);
        }

        $workout_plan->delete();

        return ApiResponse::success('Workout plan deleted.');
    }

    public function clearAll(Request $request): JsonResponse
    {
        WorkoutPlan::where('user_id', $request->user()->id)->delete();

        return ApiResponse::success('All workout plans deleted.');
    }
}
