<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class SopDocument extends Model
{
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
        return $this->belongsTo(SopCategory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(SopDepartment::class);
    }

    public function sourceApp(): BelongsTo
    {
        return $this->belongsTo(SopSourceApp::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(SopTag::class, 'sop_document_tag', 'sop_document_id', 'sop_tag_id');
    }
}
