<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Referral> */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'referrer_code_id' => ReferralCode::factory(),
            'referred_anonymous_id' => (string) fake()->uuid(),
            'referred_user_id' => null,
            'referred_patient_id' => null,
            'status' => Referral::STATUS_PENDING,
            'converted_at' => null,
            'meta' => null,
        ];
    }
}
