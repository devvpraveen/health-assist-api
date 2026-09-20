<?php

namespace App\Actions\Marketing;

use App\Models\EmailWorkflow;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateEmailWorkflowAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(EmailWorkflow $workflow, array $data): EmailWorkflow
    {
        return DB::transaction(function () use ($workflow, $data): EmailWorkflow {
            $workflow->fill(collect($data)->only(['name', 'trigger', 'is_active', 'steps', 'key'])->all());
            $workflow->save();

            $this->auditLogger->log('marketing.email_workflow.updated', $workflow, [
                'key' => $workflow->key,
            ]);

            return $workflow->refresh();
        });
    }
}
