<?php

namespace App\Features\Workoutplan\Controllers;

use App\Http\Controllers\Controller;

use App\Models\WorkoutPlan;
use App\Models\Notification;
use App\Support\ApiResponse;
use App\Support\RequiresConfirmation;
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
            'title'   => 'New Workout Added',
            'body'    => "Workout \"{$workout->name}\" has been added for " . \Carbon\Carbon::parse($workout->date)->format('d-m-Y') . ".",
            'notification_type' => 'workout_plan',
            'is_read' => false,
        ]);

        return ApiResponse::success('Workout plan created.', $workout, 201);
    }

    public function show(Request $request, WorkoutPlan $workout_plan): JsonResponse
    {
        if ($unauthorized = $this->authorizeOwner($request, $workout_plan)) {
            return $unauthorized;
        }

        return ApiResponse::success('Workout plan loaded.', $workout_plan);
    }

    public function update(Request $request, WorkoutPlan $workout_plan): JsonResponse
    {
        if ($unauthorized = $this->authorizeOwner($request, $workout_plan)) {
            return $unauthorized;
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
        if ($confirmation = RequiresConfirmation::check($request, 'delete_workout_plan', $workout_plan->name)) {
            return $confirmation;
        }

        if ($unauthorized = $this->authorizeOwner($request, $workout_plan)) {
            return $unauthorized;
        }

        $workout_plan->delete();

        return ApiResponse::success('Workout plan deleted.');
    }

    public function clearAll(Request $request): JsonResponse
    {
        if ($confirmation = RequiresConfirmation::check($request, 'clear_workout_plans', 'all workout plans')) {
            return $confirmation;
        }

        WorkoutPlan::where('user_id', $request->user()->id)->delete();

        return ApiResponse::success('All workout plans deleted.');
    }

    private function authorizeOwner(Request $request, WorkoutPlan $workoutPlan): ?JsonResponse
    {
        if ($workoutPlan->user_id === $request->user()->id) {
            return null;
        }

        return ApiResponse::error('Unauthorized.', [], 403);
    }
}
