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
        'workout_plan_id',
        'workout_exercise_id',
        'exercise_name',
        'workout_date',
        'actual_sets',
        'actual_reps',
        'actual_weight_kg',
        'sets',
        'reps',
        'weight',
        'duration_minutes',
        'tracked_at',
        'is_completed',
        'completed',
        'notes',
        'logged_at',
    ];

    protected $casts = [
        'workout_date' => 'date',
        'tracked_at' => 'date',
        'actual_sets' => 'integer',
        'actual_reps' => 'integer',
        'actual_weight_kg' => 'integer',
        'sets' => 'integer',
        'reps' => 'integer',
        'weight' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_completed' => 'boolean',
        'completed' => 'boolean',
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

    public function workoutPlan()
    {
        return $this->belongsTo(WorkoutPlan::class);
    }
}
