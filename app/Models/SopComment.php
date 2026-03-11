<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sop_id',
        'user_id',
        'comment_text',
    ];

    public function sop(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'sop_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
