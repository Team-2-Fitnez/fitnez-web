<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerDetail extends Model
{
    protected $table = 'trainer_details';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'specialization',
        'biography',
        'experience_years',
        'hourly_rate',
        'avg_rating',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'hourly_rate' => 'decimal:2',
        'avg_rating' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
