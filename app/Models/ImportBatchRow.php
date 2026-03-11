<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'status',
        'error_message',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }
}
