<?php

namespace App\Actions\Marketing;

use App\Models\AttributionTouch;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CaptureAttributionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?int $userId = null): AttributionTouch
    {
        return DB::transaction(function () use ($data, $userId): AttributionTouch {
            return AttributionTouch::query()->create([
                'tenant_id' => $data['tenant_id'] ?? TenantContext::id(),
                'anonymous_id' => $data['anonymous_id'],
                'user_id' => $userId,
                'patient_id' => $data['patient_id'] ?? null,
                'campaign' => $data['campaign'] ?? null,
                'source' => $data['source'] ?? null,
                'medium' => $data['medium'] ?? null,
                'content' => $data['content'] ?? null,
                'term' => $data['term'] ?? null,
                'referrer' => $data['referrer'] ?? null,
                'landing_path' => $data['landing_path'] ?? null,
                'captured_at' => now(),
                'meta' => $data['meta'] ?? null,
            ]);
        });
    }
}
