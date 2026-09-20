<?php

namespace Database\Factories;

use App\Models\AttributionTouch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttributionTouch> */
class AttributionTouchFactory extends Factory
{
    protected $model = AttributionTouch::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'anonymous_id' => (string) fake()->uuid(),
            'user_id' => null,
            'patient_id' => null,
            'campaign' => 'spring',
            'source' => 'google',
            'medium' => 'cpc',
            'content' => 'ad1',
            'term' => 'physio',
            'referrer' => 'https://example.com',
            'landing_path' => '/en/conditions/back-pain',
            'captured_at' => now(),
            'meta' => null,
        ];
    }
}
