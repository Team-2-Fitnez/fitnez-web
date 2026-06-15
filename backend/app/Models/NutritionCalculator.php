<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionCalculator extends Model
{
    protected $table = 'nutrition_calculator';

    const CREATED_AT = 'calculated_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'bmr',
        'tdee',
        'target_calories',
        'target_protein',
        'target_carbs',
        'target_fat',
        'calculated_at',
    ];

    protected $casts = [
        'bmr' => 'decimal:2',
        'tdee' => 'decimal:2',
        'target_calories' => 'decimal:2',
        'target_protein' => 'decimal:2',
        'target_carbs' => 'decimal:2',
        'target_fat' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
