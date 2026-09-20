<?php

namespace App\Services\Marketing;

use App\Jobs\Marketing\AdvanceEmailWorkflowRunJob;
use App\Models\EmailSubscription;
use App\Models\EmailWorkflow;
use App\Models\EmailWorkflowRun;
use Illuminate\Support\Facades\DB;

class EmailWorkflowService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function startForTrigger(string $trigger, EmailSubscription $subscription, array $meta = []): void
    {
        if (! $subscription->isSubscribed()) {
            return;
        }

        $workflows = EmailWorkflow::query()
            ->withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->where('trigger', $trigger)
            ->where(function ($q) use ($subscription): void {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $subscription->tenant_id);
            })
            ->get();

        foreach ($workflows as $workflow) {
            $this->startRun($workflow, $subscription, $meta);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function startRun(EmailWorkflow $workflow, EmailSubscription $subscription, array $meta = []): EmailWorkflowRun
    {
        $run = DB::transaction(function () use ($workflow, $subscription, $meta): EmailWorkflowRun {
            return EmailWorkflowRun::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'workflow_id' => $workflow->id,
                'subscription_id' => $subscription->id,
                'status' => EmailWorkflowRun::STATUS_PENDING,
                'current_step' => 0,
                'meta' => $meta,
            ]);
        });

        AdvanceEmailWorkflowRunJob::dispatch($run->id);

        return $run;
    }
}
