<?php

namespace App\Actions\Marketing;

use App\Models\Referral;
use App\Models\ReferralReward;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ConvertReferralAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Referral $referral, array $data = []): Referral
    {
        if ($referral->status === Referral::STATUS_CONVERTED) {
            return $referral;
        }

        return DB::transaction(function () use ($referral, $data): Referral {
            $referral->forceFill([
                'status' => Referral::STATUS_CONVERTED,
                'converted_at' => now(),
                'referred_user_id' => $data['referred_user_id'] ?? $referral->referred_user_id,
                'referred_patient_id' => $data['referred_patient_id'] ?? $referral->referred_patient_id,
                'meta' => array_merge($referral->meta ?? [], $data['meta'] ?? []),
            ])->save();

            $reward = $referral->rewards()->first();
            if ($reward) {
                $reward->forceFill([
                    'type' => $data['reward_type'] ?? $reward->type,
                    'amount_cents' => $data['amount_cents'] ?? $reward->amount_cents,
                    'status' => ReferralReward::STATUS_GRANTED,
                ])->save();
            }

            $this->auditLogger->log('marketing.referral.converted', $referral, [
                'referral_uuid' => $referral->uuid,
            ]);

            return $referral->refresh()->load('rewards');
        });
    }
}
