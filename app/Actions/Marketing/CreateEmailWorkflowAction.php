<?php

namespace App\Actions\Marketing;

use App\Models\EmailWorkflow;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateEmailWorkflowAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): EmailWorkflow
    {
        return DB::transaction(function () use ($data): EmailWorkflow {
            $tenantId = array_key_exists('tenant_id', $data)
                ? $data['tenant_id']
                : TenantContext::id();

            $workflow = EmailWorkflow::query()->create([
                'tenant_id' => $tenantId,
                'tenant_key' => $tenantId ? 'tenant:'.$tenantId : 'system',
                'key' => $data['key'] ?? Str::slug($data['name']),
                'name' => $data['name'],
                'trigger' => $data['trigger'],
                'is_active' => $data['is_active'] ?? true,
                'steps' => $data['steps'] ?? [],
            ]);

            $this->auditLogger->log('marketing.email_workflow.created', $workflow, [
                'key' => $workflow->key,
                'trigger' => $workflow->trigger,
            ]);

            return $workflow;
        });
    }
}
