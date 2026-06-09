<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\TrainerMonitoringRequest;
use App\Models\FoodLog;
use App\Models\MealPlan;
use App\Models\NutritionCalculator;
use App\Models\TrainerBooking;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\WorkoutTracking;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MemberFitnessMonitoringController extends Controller
{
    public function summary(Request $request)
    {
        $trainerId = $request->user()->id;
        $today = now()->toDateString();

        $memberQuery = $this->bookedMembersQuery($trainerId, $today);
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

        $recentTrackings = WorkoutTracking::query()
            ->whereIn('user_id', $membersPluckQuery)
            ->with(['user', 'workoutExercise.exercise'])
            ->orderByDesc('logged_at')
            ->limit(10)
            ->get();

        return ApiResponse::success('Trainer monitoring summary loaded.', [
            'total_members' => $membersNow,
            'total_members_trend' => $membersTrend,
            'active_workout_plans' => WorkoutPlan::query()->whereIn('user_id', $membersPluckQuery)->where('completed', false)->count(),
            'active_workout_plans_trend' => $plansTrend,
            'completed_trackings' => WorkoutTracking::query()->whereIn('user_id', $membersPluckQuery)->where('is_completed', true)->count(),
            'completed_trackings_trend' => $logsTrend,
            'meal_plans' => MealPlan::query()->whereIn('user_id', $membersPluckQuery)->count(),
            'meal_plans_trend' => $mealsTrend,
            'nutrition_calculations' => NutritionCalculator::query()->whereIn('user_id', $membersPluckQuery)->count(),
            'recent_trackings' => $recentTrackings,
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
        $trainerId = $request->user()->id;
        $today = now()->toDateString();

        $members = $this->bookedMembersQuery($trainerId, $today)
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
        if ($member->roleName() === 'admin') {
            return ApiResponse::error('This member cannot be opened from trainer monitoring.', [], 403);
        }

        $trainerId = $request->user()->id;
        $today = now()->toDateString();

        $hasActiveBooking = TrainerBooking::query()
            ->where('member_id', $member->id)
            ->where('trainer_id', $trainerId)
            ->where('status', TrainerBooking::STATUS_CONFIRMED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if (! $hasActiveBooking) {
            return ApiResponse::error('You do not have an active booking for this member.', [], 403);
        }

        $workoutPlans = WorkoutPlan::query()
            ->where('user_id', $member->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(30)
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

        $mealPlan = MealPlan::query()
            ->where('user_id', $member->id)
            ->first();

        $foodLogs = FoodLog::query()
            ->where('user_id', $member->id)
            ->orderByDesc('logged_date')
            ->orderByDesc('id')
            ->limit(50)
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
            'meal_plans' => $mealPlans,
            'meal_plan' => $mealPlan,
            'food_logs' => $foodLogs,
        ]);
    }

    private function bookedMembersQuery(int $trainerId, string $today): Builder
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'member'))
            ->whereHas('trainerBookingsAsMember', function ($query) use ($trainerId, $today) {
                $query->where('trainer_id', $trainerId)
                    ->where('status', TrainerBooking::STATUS_CONFIRMED)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            });
    }
}
