<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutExercise extends Model
{
    protected $table = 'workout_exercises';

    public $timestamps = false;

    protected $fillable = [
        'workout_plan_id',
        'exercise_id',
        'day_of_week',
        'sets',
        'reps',
        'rest_seconds',
        'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'sets' => 'integer',
        'reps' => 'integer',
        'rest_seconds' => 'integer',
    ];

    public function workoutPlan()
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function trackings()
    {
        return $this->hasMany(WorkoutTracking::class);
    }
}
