<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'admin_user_id',
        'filename',
        'totals_json',
    ];

    protected $casts = [
        'totals_json' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class, 'batch_id');
    }
}
