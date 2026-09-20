<?php

namespace App\Listeners\Marketing;

use App\Models\EmailSubscription;
use App\Models\EmailWorkflow;
use App\Services\Marketing\EmailWorkflowService;
use Illuminate\Auth\Events\Registered;

/**
 * Stub listener: starts user_registered workflows when a consented subscription exists for the email.
 */
class StartUserRegisteredEmailWorkflowListener
{
    public function __construct(private EmailWorkflowService $workflows) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user->tenant_id || ! $user->email) {
            return;
        }

        $subscription = EmailSubscription::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $user->tenant_id)
            ->where('email', $user->email)
            ->first();

        if (! $subscription?->isSubscribed()) {
            return;
        }

        $this->workflows->startForTrigger(EmailWorkflow::TRIGGER_USER_REGISTERED, $subscription, [
            'user_id' => $user->id,
        ]);
    }
}
