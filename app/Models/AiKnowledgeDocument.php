<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeDocument extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'source_type',
        'status',
        'body',
        'meta',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'approved_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ARCHIVED = 'archived';

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
