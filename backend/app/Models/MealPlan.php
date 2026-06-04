<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealPlan extends Model
{
    protected $fillable = [
        'user_id',
        'daily_limit',
        'bmr',
        'tdee',
        'target_kal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }
}