<?php

namespace App\Features\Mealplan\Controllers;

use App\Http\Controllers\Controller;

use App\Models\FoodLog;
use App\Models\MealPlan;
use App\Support\ActionConfirmation;
use App\Support\ApiResponse;
use App\Support\QueryLimit;
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
    public function getFoodLog(Request $request)
    {
        $foods = FoodLog::where('user_id', Auth::id())
            ->whereDate('logged_date', today())
            ->orderBy('created_at', 'asc')
            ->limit(QueryLimit::limit($request, 100, 100))
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
    public function deleteFood(Request $request, $id)
    {
        $food = FoodLog::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($response = ActionConfirmation::require($request, 'delete food log', $food->food_name ?? ('food log #' . $food->id))) {
            return $response;
        }

        $food->delete();

        return ApiResponse::success('Food log deleted.', ['deleted_id' => $food->id]);
    }
}
