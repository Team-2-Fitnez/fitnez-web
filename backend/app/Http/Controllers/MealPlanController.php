<?php

namespace App\Http\Controllers;

use App\Models\FoodLog;
use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MealPlanController extends Controller
{
    // GET /api/user/meal_plan
    public function getMealPlan()
    {
        $plan = MealPlan::where('user_id', Auth::id())->first();
        return response()->json(['plan' => $plan]);
    }

    // PUT /api/user/meal_plan
    public function saveMealPlan(Request $request)
    {
        $request->validate([
            'daily_limit' => 'required|integer|min:0',
            'bmr'         => 'required|integer|min:0',
            'tdee'        => 'required|integer|min:0',
            'target_kal'  => 'required|integer|min:0',
        ]);

        $plan = MealPlan::where('user_id', Auth::id())->first();

        if ($plan) {
            $plan->update([
                'daily_limit' => $request->daily_limit,
                'bmr'         => $request->bmr,
                'tdee'        => $request->tdee,
                'target_kal'  => $request->target_kal,
            ]);
        } else {
            $plan = MealPlan::create([
                'user_id'     => Auth::id(),
                'daily_limit' => $request->daily_limit,
                'bmr'         => $request->bmr,
                'tdee'        => $request->tdee,
                'target_kal'  => $request->target_kal,
            ]);
        }

        return response()->json(['plan' => $plan]);
    }

    // GET /api/user/food_log
    public function getFoodLog()
    {
        $foods = FoodLog::where('user_id', Auth::id())
            ->whereDate('logged_date', today())
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['foods' => $foods]);
    }

    // POST /api/user/food_log
    public function addFood(Request $request)
    {
        $request->validate([
            'food_name' => 'required|string|max:255',
            'calories'  => 'required|integer|min:1',
        ]);

        $food = FoodLog::create([
            'user_id'     => Auth::id(),
            'food_name'   => $request->food_name,
            'calories'    => $request->calories,
            'logged_date' => today(),
        ]);

        return response()->json($food);
    }

    // DELETE /api/user/food_log/{id}
    public function deleteFood($id)
    {
        $food = FoodLog::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $food->delete();

        return response()->json(['message' => 'Deleted']);
    }

    // GET /api/admin/nutrition-monitoring
    public function adminMonitoring()
    {
        $members = MealPlan::with('user')
            ->get()
            ->map(function ($plan) {
                $totalCalories = FoodLog::where('user_id', $plan->user_id)
                    ->whereDate('logged_date', today())
                    ->sum('calories');

                return [
                    'id'             => $plan->user_id,
                    'name'           => $plan->user->name,
                    'email'          => $plan->user->email,
                    'daily_limit'    => $plan->daily_limit,
                    'total_calories' => $totalCalories,
                ];
            });

        return response()->json(['members' => $members]);
    }
}