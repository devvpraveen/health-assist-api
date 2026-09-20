<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversationMemory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'conversation_ref',
        'agent',
        'patient_id',
        'user_id',
        'structured_json',
        'expires_at',
    ];

    protected $casts = [
        'structured_json' => 'array',
        'expires_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
