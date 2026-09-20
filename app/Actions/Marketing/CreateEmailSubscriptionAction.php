<?php

namespace App\Actions\Marketing;

use App\Models\EmailSubscription;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateEmailSubscriptionAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array{email: string, tenant_id?: int|null, consented?: bool}  $data
     */
    public function handle(array $data): EmailSubscription
    {
        return DB::transaction(function () use ($data): EmailSubscription {
            $tenantId = $data['tenant_id'] ?? TenantContext::id();

            $subscription = EmailSubscription::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'email' => strtolower($data['email']),
                ],
                [
                    'user_id' => Auth::id(),
                    'consented_at' => ($data['consented'] ?? true) ? now() : null,
                    'unsubscribed_at' => null,
                ]
            );

            if (empty($subscription->unsubscribe_token)) {
                $subscription->forceFill(['unsubscribe_token' => Str::random(48)])->save();
            }

            $this->auditLogger->log('marketing.email.subscribed', $subscription, [
                'email' => $subscription->email,
            ]);

            return $subscription->refresh();
        });
    }
}
