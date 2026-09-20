<?php

namespace Database\Factories;

use App\Models\ReferralCode;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ReferralCode> */
class ReferralCodeFactory extends Factory
{
    protected $model = ReferralCode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'code' => strtoupper(Str::random(8)),
            'owner_user_id' => null,
            'owner_patient_id' => null,
            'campaign' => 'friends',
            'is_active' => true,
            'max_uses' => null,
            'uses_count' => 0,
        ];
    }
}
