<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SopTag extends Model
{
    protected $fillable = ['name'];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\SopDocument::class, // <- pakai FQCN biar Intelephense gak drama
            'sop_document_tag',
            'sop_tag_id',
            'sop_document_id'
        );
    }
}
