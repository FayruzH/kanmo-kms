<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class SopTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(SopDocument::class, 'sop_document_tag', 'sop_tag_id', 'sop_document_id');
    }
}
