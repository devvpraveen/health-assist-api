<?php

namespace App\Actions\Automation;

use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowVersion;
use App\Models\User;
use App\Services\Automation\WorkflowEngine;
use Illuminate\Support\Facades\DB;

class PublishAutomationWorkflowAction
{
    public function __construct(private WorkflowEngine $engine) {}

    public function handle(AutomationWorkflow $workflow, AutomationWorkflowVersion $version, ?User $actor = null): AutomationWorkflowVersion
    {
        abort_unless($version->workflow_id === $workflow->id, 404);
        $this->engine->assertStepsSafe($version->steps ?? []);

        return DB::transaction(function () use ($workflow, $version, $actor): AutomationWorkflowVersion {
            AutomationWorkflowVersion::query()
                ->where('workflow_id', $workflow->id)
                ->where('status', AutomationWorkflowVersion::STATUS_PUBLISHED)
                ->where('id', '!=', $version->id)
                ->update(['status' => AutomationWorkflowVersion::STATUS_ARCHIVED]);

            $version->update([
                'status' => AutomationWorkflowVersion::STATUS_PUBLISHED,
                'published_at' => now(),
                'created_by_user_id' => $version->created_by_user_id ?? $actor?->id,
            ]);

            $workflow->update([
                'status' => AutomationWorkflow::STATUS_PUBLISHED,
                'is_active' => true,
                'active_version_id' => $version->id,
            ]);

            return $version->fresh();
        });
    }
}
