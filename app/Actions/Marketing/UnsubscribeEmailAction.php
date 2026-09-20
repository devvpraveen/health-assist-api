<?php

namespace App\Actions\Marketing;

use App\Models\EmailSubscription;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnsubscribeEmailAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(string $token): EmailSubscription
    {
        return DB::transaction(function () use ($token): EmailSubscription {
            $subscription = EmailSubscription::query()
                ->withoutGlobalScope('tenant')
                ->where('unsubscribe_token', $token)
                ->first();

            if (! $subscription) {
                throw ValidationException::withMessages([
                    'token' => 'Invalid unsubscribe token.',
                ]);
            }

            $subscription->forceFill(['unsubscribed_at' => now()])->save();

            $this->auditLogger->log('marketing.email.unsubscribed', $subscription, [
                'email' => $subscription->email,
            ]);

            return $subscription;
        });
    }
}
