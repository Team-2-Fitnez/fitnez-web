<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutTracking extends Model
{
    protected $table = 'workout_trackings';

    const CREATED_AT = 'logged_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'workout_exercise_id',
        'workout_date',
        'actual_sets',
        'actual_reps',
        'actual_weight_kg',
        'is_completed',
        'logged_at',
    ];

    protected $casts = [
        'workout_date' => 'date',
        'actual_sets' => 'integer',
        'actual_reps' => 'integer',
        'actual_weight_kg' => 'integer',
        'is_completed' => 'boolean',
        'logged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workoutExercise()
    {
        return $this->belongsTo(WorkoutExercise::class);
    }
}
