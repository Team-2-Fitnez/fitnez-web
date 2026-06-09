<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\TrainerMonitoringRequest;
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
        $memberRoleId = Role::query()->where('name', 'member')->value('id');

        $memberQuery = User::query()
            ->where('id', '!=', $request->user()->id)
            ->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId));

        $membersPluckQuery = (clone $memberQuery)->pluck('id');

        $membersNow = (clone $memberQuery)->count();
        $membersPrev = (clone $memberQuery)->where('created_at', '<=', now()->subDays(7))->count();
        $membersTrend = $this->calculateTrend($membersNow, $membersPrev);

        $plansNow = WorkoutPlan::query()->whereIn('user_id', $membersPluckQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $plansPrev = WorkoutPlan::query()->whereIn('user_id', $membersPluckQuery)->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
        $plansTrend = $this->calculateTrend($plansNow, $plansPrev);

        $logsNow = WorkoutTracking::query()->whereIn('user_id', $membersPluckQuery)->where('is_completed', true)->where('logged_at', '>=', now()->subDays(7))->count();
        $logsPrev = WorkoutTracking::query()->whereIn('user_id', $membersPluckQuery)->where('is_completed', true)->whereBetween('logged_at', [now()->subDays(14), now()->subDays(7)])->count();
        $logsTrend = $this->calculateTrend($logsNow, $logsPrev);

        $mealsNow = MealPlan::query()->whereIn('user_id', $membersPluckQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $mealsPrev = MealPlan::query()->whereIn('user_id', $membersPluckQuery)->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
        $mealsTrend = $this->calculateTrend($mealsNow, $mealsPrev);

        return ApiResponse::success('Trainer monitoring summary loaded.', [
            'total_members' => $membersNow,
            'total_members_trend' => $membersTrend,
            'active_workout_plans' => WorkoutPlan::query()->whereIn('user_id', $membersPluckQuery)->where('status', 'active')->count(),
            'active_workout_plans_trend' => $plansTrend,
            'completed_trackings' => WorkoutTracking::query()->whereIn('user_id', $membersPluckQuery)->where('is_completed', true)->count(),
            'completed_trackings_trend' => $logsTrend,
            'meal_plans' => MealPlan::query()->whereIn('user_id', $membersPluckQuery)->count(),
            'meal_plans_trend' => $mealsTrend,
            'nutrition_calculations' => NutritionCalculator::query()->whereIn('user_id', $membersPluckQuery)->count(),
        ]);
    }

    private function calculateTrend(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function members(TrainerMonitoringRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);
        $memberRoleId = Role::query()->where('name', 'member')->value('id');

        $members = User::query()
            ->with('role')
            ->where('id', '!=', $request->user()->id)
            ->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))
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
                'meal_plans_count' => MealPlan::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('meal_plans.user_id', 'users.id'),
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

        $workoutPlans = WorkoutPlan::query()
            ->where('user_id', $member->id)
            ->with(['workoutExercises' => function ($query) {
                $query->with('exercise')->orderBy('day_of_week')->orderBy('id');
            }])
            ->orderByDesc('date')
            ->limit(10)
            ->get();

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
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return ApiResponse::success('Member fitness detail loaded.', [
            'member' => $member->load('role'),
            'summary' => [
                'workout_plans' => WorkoutPlan::query()->where('user_id', $member->id)->count(),
                'active_workout_plans' => WorkoutPlan::query()->where('user_id', $member->id)->where('status', 'active')->count(),
                'completed_trackings' => WorkoutTracking::query()->where('user_id', $member->id)->where('is_completed', true)->count(),
                'incomplete_trackings' => WorkoutTracking::query()->where('user_id', $member->id)->where('is_completed', false)->count(),
                'meal_plans' => MealPlan::query()->where('user_id', $member->id)->count(),
                'latest_nutrition_calculated_at' => $nutrition?->calculated_at,
            ],
            'workout_plans' => $workoutPlans,
            'workout_trackings' => $trackings,
            'nutrition' => $nutrition,
            'meal_plans' => $mealPlans,
        ]);
    }
}
