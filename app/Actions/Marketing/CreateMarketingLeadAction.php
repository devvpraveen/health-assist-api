<?php

namespace App\Actions\Marketing;

use App\Events\Marketing\MarketingLeadCreated;
use App\Models\EmailSubscription;
use App\Models\EmailWorkflow;
use App\Models\MarketingLead;
use App\Services\AuditLogger;
use App\Services\Marketing\EmailWorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateMarketingLeadAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private EmailWorkflowService $workflows,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): MarketingLead
    {
        $lead = DB::transaction(function () use ($data): MarketingLead {
            $lead = MarketingLead::query()->create([
                'tenant_id' => $data['tenant_id'] ?? TenantContext::id(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'source' => $data['source'] ?? null,
                'campaign' => $data['campaign'] ?? null,
                'provider_interest' => $data['provider_interest'] ?? null,
                'status' => $data['status'] ?? MarketingLead::STATUS_NEW,
                'appointment_id' => $data['appointment_id'] ?? null,
                'converted_at' => $data['converted_at'] ?? null,
                'attribution' => $data['attribution'] ?? null,
            ]);

            $this->auditLogger->log('marketing.lead.created', $lead, [
                'lead_uuid' => $lead->uuid,
                'source' => $lead->source,
            ]);

            return $lead;
        });

        event(new MarketingLeadCreated($lead));

        $subscription = EmailSubscription::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $lead->tenant_id)
            ->where('email', $lead->email)
            ->first();

        if ($subscription?->isSubscribed()) {
            $this->workflows->startForTrigger(EmailWorkflow::TRIGGER_LEAD_CREATED, $subscription, [
                'lead_id' => $lead->id,
            ]);
        }

        return $lead;
    }
}
