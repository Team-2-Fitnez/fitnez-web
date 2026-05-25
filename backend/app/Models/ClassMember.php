<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassMember extends Model
{
    protected $table = 'class_members';

    protected $fillable = [
        'class_id',
        'user_id',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
