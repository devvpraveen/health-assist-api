<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiWorkflowMemory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'agent',
        'intent',
        'tool_chain',
        'outcome_score',
        'success_count',
        'failure_count',
        'meta',
    ];

    protected $casts = [
        'tool_chain' => 'array',
        'meta' => 'array',
        'outcome_score' => 'float',
    ];
}
