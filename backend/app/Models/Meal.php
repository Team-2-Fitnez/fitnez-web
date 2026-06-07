<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    protected $table = 'meals';

    public $timestamps = false;

    protected $fillable = [
        'meal_plan_id',
        'meal_type',
        'food_name',
        'portion_grams',
        'calories',
        'protein',
        'carbs',
        'fat',
    ];

    protected $casts = [
        'portion_grams' => 'integer',
        'calories' => 'decimal:2',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
    ];

    public function mealPlan()
    {
        return $this->belongsTo(MealPlan::class);
    }
}
