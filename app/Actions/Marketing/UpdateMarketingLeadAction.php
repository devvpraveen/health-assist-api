<?php

namespace App\Actions\Marketing;

use App\Models\MarketingLead;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateMarketingLeadAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(MarketingLead $lead, array $data): MarketingLead
    {
        return DB::transaction(function () use ($lead, $data): MarketingLead {
            if (($data['status'] ?? null) === MarketingLead::STATUS_CONVERTED && empty($data['converted_at']) && ! $lead->converted_at) {
                $data['converted_at'] = now();
            }

            $lead->fill($data);
            $lead->save();

            $this->auditLogger->log('marketing.lead.updated', $lead, [
                'lead_uuid' => $lead->uuid,
                'status' => $lead->status,
            ]);

            return $lead->refresh();
        });
    }
}
