<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $table = 'exercises';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'muscle_group',
        'equipment',
        'tutorial_video_url',
        'tutorial_image_url',
        'instructions',
    ];

    public function workoutExercises()
    {
        return $this->hasMany(WorkoutExercise::class);
    }
}
