<?php

namespace App\Actions\Marketing;

use App\Models\AudienceSegment;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAudienceSegmentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): AudienceSegment
    {
        return DB::transaction(function () use ($data): AudienceSegment {
            $segment = AudienceSegment::query()->create([
                'tenant_id' => $data['tenant_id'] ?? TenantContext::id(),
                'key' => $data['key'] ?? Str::slug($data['name']),
                'name' => $data['name'],
                'definition' => $this->sanitizeDefinition($data['definition'] ?? []),
            ]);

            $this->auditLogger->log('marketing.segment.created', $segment, [
                'key' => $segment->key,
            ]);

            return $segment;
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function sanitizeDefinition(array $definition): array
    {
        $allowed = ['inactive_days', 'new_user', 'new_user_days', 'appointment_upcoming'];

        return collect($definition)
            ->only($allowed)
            ->all();
    }
}
