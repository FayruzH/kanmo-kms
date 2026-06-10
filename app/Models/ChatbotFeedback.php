<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFeedback extends Model
{
    use HasFactory;

    protected $table = 'chatbot_feedback';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'question',
        'detail',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
