<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\TrainerMonitoringRequest;
use App\Models\FoodLog;
use App\Models\MealPlan;
use App\Models\NutritionCalculator;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\WorkoutTracking;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberFitnessMonitoringController extends Controller
{
    public function summary(Request $request)
    {
        $memberQuery = $this->visibleMemberQuery($request);

        $memberIds = (clone $memberQuery)->pluck('id');
        $completedTrackings = WorkoutTracking::query()->whereIn('user_id', $memberIds)->where('is_completed', true)->count();
        $nutritionCount = NutritionCalculator::query()->whereIn('user_id', $memberIds)->count();
        $mealPlanCount = MealPlan::query()->whereIn('user_id', $memberIds)->count();

        return ApiResponse::success('Trainer monitoring summary loaded.', [
            'total_members' => (clone $memberQuery)->count(),
            'active_workout_plans' => WorkoutPlan::query()->whereIn('user_id', $memberIds)->where('completed', false)->count(),
            'completed_trackings' => $completedTrackings,
            'completed_workouts' => $completedTrackings,
            'meal_plans' => $mealPlanCount,
            'nutrition_calculations' => $nutritionCount,
            'nutrition_records' => $nutritionCount,
        ]);
    }

    public function members(TrainerMonitoringRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $members = $this->visibleMemberQuery($request)
            ->with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search)
                        ->orWhere('phone', 'ilike', $search);
                });
            })
            ->withCount([
                'trainerApplications',
                'trainerDetail',
            ])
            ->select('users.*')
            ->addSelect([
                'workout_plans_count' => WorkoutPlan::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('workout_plans.user_id', 'users.id'),
                'workout_trackings_count' => WorkoutTracking::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('workout_trackings.user_id', 'users.id'),
                'completed_workouts_count' => WorkoutTracking::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('workout_trackings.user_id', 'users.id')
                    ->where('is_completed', true),
                'meal_plans_count' => MealPlan::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('meal_plans.user_id', 'users.id'),
                'nutrition_records_count' => NutritionCalculator::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('nutrition_calculator.user_id', 'users.id'),
                'latest_nutrition_calculated_at' => NutritionCalculator::query()
                    ->select('calculated_at')
                    ->whereColumn('nutrition_calculator.user_id', 'users.id')
                    ->latest('calculated_at')
                    ->limit(1),
            ])
            ->orderByDesc('id')
            ->paginate($request->perPage(10));

        return ApiResponse::success('Members loaded for trainer monitoring.', $members);
    }

    public function show(Request $request, User $member)
    {
        if ($member->roleName() === 'admin' || $member->id === $request->user()->id) {
            return ApiResponse::error('This member cannot be opened from trainer monitoring.', [], 403);
        }

        if (! $this->visibleMemberQuery($request)->where('users.id', $member->id)->exists()) {
            return ApiResponse::error('This member is not connected to this trainer.', [], 403);
        }

        $workoutPlans = WorkoutPlan::query()
            ->where('user_id', $member->id)
            ->with(['workoutExercises' => function ($query) {
                $query->with('exercise')->orderBy('day_of_week')->orderBy('id');
            }])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (WorkoutPlan $plan) {
                $plan->setAttribute('title', $plan->name);
                $plan->setAttribute('description', trim(implode(' ', array_filter([$plan->category, $plan->day]))));
                $plan->setAttribute('status', $plan->completed ? 'completed' : 'active');
                $plan->setAttribute('start_date', $plan->date);
                $plan->setAttribute('end_date', $plan->date);
                return $plan;
            });

        $trackings = WorkoutTracking::query()
            ->where('user_id', $member->id)
            ->with(['workoutExercise.exercise', 'workoutExercise.workoutPlan'])
            ->orderByDesc('workout_date')
            ->orderByDesc('logged_at')
            ->limit(25)
            ->get();

        $nutrition = NutritionCalculator::query()
            ->where('user_id', $member->id)
            ->latest('calculated_at')
            ->first();

        $mealPlans = MealPlan::query()
            ->where('user_id', $member->id)
            ->with('meals')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (MealPlan $plan) {
                $plan->setAttribute('title', 'Meal Plan #'.$plan->id);
                $plan->setAttribute('total_calories', $plan->target_kal ?: $plan->daily_limit);
                $plan->setAttribute('protein_grams', null);
                $plan->setAttribute('carbs_grams', null);
                $plan->setAttribute('fat_grams', null);
                $plan->setAttribute('plan_date', $plan->created_at);
                return $plan;
            });

        $foodLogs = FoodLog::query()
            ->where('user_id', $member->id)
            ->orderByDesc('logged_date')
            ->limit(25)
            ->get();

        return ApiResponse::success('Member fitness detail loaded.', [
            'member' => $member->load('role'),
            'summary' => [
                'workout_plans' => WorkoutPlan::query()->where('user_id', $member->id)->count(),
                'active_workout_plans' => WorkoutPlan::query()->where('user_id', $member->id)->where('completed', false)->count(),
                'completed_trackings' => WorkoutTracking::query()->where('user_id', $member->id)->where('is_completed', true)->count(),
                'incomplete_trackings' => WorkoutTracking::query()->where('user_id', $member->id)->where('is_completed', false)->count(),
                'meal_plans' => MealPlan::query()->where('user_id', $member->id)->count(),
                'latest_nutrition_calculated_at' => $nutrition?->calculated_at,
            ],
            'workout_plans' => $workoutPlans,
            'workout_trackings' => $trackings,
            'nutrition' => $nutrition,
            'nutrition_calculator' => $nutrition ? [$nutrition] : [],
            'meal_plans' => $mealPlans,
            'food_logs' => $foodLogs,
        ]);
    }

    private function visibleMemberQuery(Request $request)
    {
        $memberRoleId = Role::query()->where('name', 'member')->value('id');
        $connectedMemberIds = DB::table('trainer_bookings')
            ->where('trainer_id', $request->user()->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->pluck('member_id')
            ->unique()
            ->values();

        return User::query()
            ->whereIn('users.id', $connectedMemberIds)
            ->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId));
    }
}
