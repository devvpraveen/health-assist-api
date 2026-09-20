<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ReferralReward> */
class ReferralRewardFactory extends Factory
{
    protected $model = ReferralReward::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'referral_id' => Referral::factory(),
            'type' => ReferralReward::TYPE_NONE,
            'status' => ReferralReward::STATUS_PENDING,
            'amount_cents' => null,
        ];
    }
}
