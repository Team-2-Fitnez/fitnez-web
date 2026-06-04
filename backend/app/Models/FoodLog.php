<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodLog extends Model
{
    protected $fillable = [
        'user_id',
        'food_name',
        'calories',
        'logged_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}