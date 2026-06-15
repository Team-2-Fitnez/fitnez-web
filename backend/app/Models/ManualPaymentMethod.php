<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ManualPaymentMethod extends Model
{
    protected $fillable = [
        'code',
        'type',
        'display_name',
        'bank_name',
        'account_number',
        'account_name',
        'qris_image_url',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
