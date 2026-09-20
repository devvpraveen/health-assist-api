<?php

namespace App\Jobs\Marketing;

use App\Models\EmailWorkflowRun;
use App\Notifications\Marketing\MarketingWorkflowMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class AdvanceEmailWorkflowRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $run = EmailWorkflowRun::query()
            ->withoutGlobalScope('tenant')
            ->with(['workflow', 'subscription'])
            ->find($this->runId);

        if (! $run || ! $run->workflow || ! $run->subscription) {
            return;
        }

        if (! $run->subscription->isSubscribed()) {
            $run->forceFill(['status' => EmailWorkflowRun::STATUS_CANCELLED])->save();

            return;
        }

        $steps = $run->workflow->steps ?? [];
        $index = (int) $run->current_step;

        if ($index >= count($steps)) {
            $run->forceFill(['status' => EmailWorkflowRun::STATUS_COMPLETED])->save();

            return;
        }

        $step = $steps[$index] ?? [];
        $subject = (string) ($step['subject'] ?? $run->workflow->name);
        $body = (string) ($step['body'] ?? '');

        $run->forceFill(['status' => EmailWorkflowRun::STATUS_RUNNING])->save();

        Notification::route('mail', $run->subscription->email)
            ->notify(new MarketingWorkflowMail(
                subjectLine: $subject,
                bodyText: $body,
                unsubscribeToken: $run->subscription->unsubscribe_token,
            ));

        $run->forceFill([
            'current_step' => $index + 1,
            'status' => ($index + 1) >= count($steps)
                ? EmailWorkflowRun::STATUS_COMPLETED
                : EmailWorkflowRun::STATUS_PENDING,
        ])->save();

        if ($run->status === EmailWorkflowRun::STATUS_PENDING) {
            self::dispatch($run->id)->delay(now()->addSeconds((int) ($step['delay_seconds'] ?? 0)));
        }
    }
}
