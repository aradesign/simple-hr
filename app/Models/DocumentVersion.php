<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
        'notes',
    ];

    protected $casts = [
            'version_number' => 'integer',
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('version_number');
    }
}
