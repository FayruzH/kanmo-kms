<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderJob extends Model
{
    protected $fillable = [
        'sop_id',
        'pic_user_id',
        'reminder_type',
        'sent_at',
        'status',
        'meta_json',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function sop(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'sop_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}
