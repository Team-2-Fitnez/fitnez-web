<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MembershipPackage extends Model
{
    protected $fillable = [
        'code',
        'name',
        'duration_months',
        'price',
        'free_class_access',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'price' => 'integer',
        'free_class_access' => 'boolean',
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
