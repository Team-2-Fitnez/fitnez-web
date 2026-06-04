<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerApplication extends Model
{
    protected $table = 'trainer_applications';

    const CREATED_AT = 'submitted_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'cv_document_url',
        'certificate_document_url',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'admin_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
