<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SopDocument extends Model
{
    use HasFactory;

    protected $table = 'sop_documents';

    protected $fillable = [
        'title',
        'category_id',
        'department_id',
        'entity',
        'source_app_id',
        'source_name',
        'type',
        'url',
        'file_path',
        'file_mime',
        'version',
        'effective_date',
        'expiry_date',
        'pic_user_id',
        'status',
        'archived_at',
        'summary',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SopCategory::class, 'category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(SopDepartment::class, 'department_id');
    }

    public function sourceApp(): BelongsTo
    {
        return $this->belongsTo(SopSourceApp::class, 'source_app_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(SopTag::class, 'sop_document_tag', 'sop_document_id', 'sop_tag_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(SopLike::class, 'sop_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SopComment::class, 'sop_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(SopActivityLog::class, 'sop_id');
    }

    public function textChunks(): HasMany
    {
        return $this->hasMany(SopTextChunk::class, 'sop_id');
    }
}
