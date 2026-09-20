<?php

namespace App\Jobs\Automation;

use App\Models\AutomationWorkflowRun;
use App\Services\Automation\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdvanceAutomationWorkflowRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $runId) {}

    public function handle(WorkflowEngine $engine): void
    {
        $run = AutomationWorkflowRun::query()
            ->withoutGlobalScopes()
            ->find($this->runId);

        if ($run === null) {
            return;
        }

        $engine->advance($run);
    }
}
