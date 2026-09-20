<?php

namespace App\Actions\Marketing;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaptureReferralAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array{code: string, anonymous_id: string, tenant_id?: int|null}  $data
     */
    public function handle(array $data): Referral
    {
        return DB::transaction(function () use ($data): Referral {
            $code = ReferralCode::query()
                ->withoutGlobalScope('tenant')
                ->where('code', strtoupper($data['code']))
                ->where('is_active', true)
                ->first();

            if (! $code) {
                throw ValidationException::withMessages([
                    'code' => 'Referral code is invalid or inactive.',
                ]);
            }

            if (! $code->hasCapacity()) {
                throw ValidationException::withMessages([
                    'code' => 'Referral code has reached its maximum uses.',
                ]);
            }

            $existing = Referral::query()
                ->withoutGlobalScope('tenant')
                ->where('referrer_code_id', $code->id)
                ->where('referred_anonymous_id', $data['anonymous_id'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $referral = Referral::query()->create([
                'tenant_id' => $code->tenant_id,
                'referrer_code_id' => $code->id,
                'referred_anonymous_id' => $data['anonymous_id'],
                'status' => Referral::STATUS_PENDING,
                'meta' => $data['meta'] ?? null,
            ]);

            $code->increment('uses_count');

            ReferralReward::query()->create([
                'tenant_id' => $code->tenant_id,
                'referral_id' => $referral->id,
                'type' => ReferralReward::TYPE_NONE,
                'status' => ReferralReward::STATUS_PENDING,
            ]);

            $this->auditLogger->log('marketing.referral.captured', $referral, [
                'code' => $code->code,
            ]);

            return $referral;
        });
    }
}
