<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'name',
        'description',
        'trainer_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_participants',
        'status',
    ];

    protected $casts = [
        'max_participants' => 'integer',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function members()
    {
        return $this->hasMany(ClassMember::class, 'class_id');
    }
}
