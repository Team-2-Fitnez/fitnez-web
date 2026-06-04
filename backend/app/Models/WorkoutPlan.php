<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutPlan extends Model
{
    protected $table = 'workout_plans';

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'date',
        'day',
        'set',
        'weight',
        'reps',
        'duration',
        'completed',
    ];

    protected $casts = [
        'date'      => 'date',
        'completed' => 'boolean',
        'set'       => 'integer',
        'weight'    => 'float',
        'reps'      => 'integer',
        'duration'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutExercises()
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    public function trackings()
    {
        return $this->hasMany(WorkoutTracking::class);
    }
}
