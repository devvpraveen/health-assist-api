<?php

namespace App\Actions\Marketing;

use App\Models\ReferralCode;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateReferralCodeAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): ReferralCode
    {
        return DB::transaction(function () use ($data): ReferralCode {
            $code = ReferralCode::query()->create([
                'tenant_id' => $data['tenant_id'] ?? TenantContext::id(),
                'code' => strtoupper($data['code'] ?? Str::random(8)),
                'owner_user_id' => $data['owner_user_id'] ?? Auth::id(),
                'owner_patient_id' => $data['owner_patient_id'] ?? null,
                'campaign' => $data['campaign'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'max_uses' => $data['max_uses'] ?? null,
                'uses_count' => 0,
            ]);

            $this->auditLogger->log('marketing.referral_code.created', $code, [
                'code' => $code->code,
            ]);

            return $code;
        });
    }
}
