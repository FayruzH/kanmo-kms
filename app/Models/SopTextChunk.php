<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopTextChunk extends Model
{
    protected $fillable = [
        'sop_id',
        'chunk_index',
        'content_text',
        'page_ref',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function sop(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'sop_id');
    }
}
