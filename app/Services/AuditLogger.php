<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function log(string $action, ?Model $subject = null, ?array $meta = null, ?User $actor = null): AuditLog
    {
        $actor ??= Auth::user();

        return AuditLog::query()->create([
            'tenant_id' => TenantContext::id() ?? $actor?->tenant_id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'meta' => $meta,
        ]);
    }
}
