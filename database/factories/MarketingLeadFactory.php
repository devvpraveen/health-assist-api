<?php

namespace Database\Factories;

use App\Models\MarketingLead;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MarketingLead> */
class MarketingLeadFactory extends Factory
{
    protected $model = MarketingLead::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'source' => 'web',
            'campaign' => 'launch',
            'provider_interest' => null,
            'status' => MarketingLead::STATUS_NEW,
            'appointment_id' => null,
            'converted_at' => null,
            'attribution' => null,
        ];
    }
}
