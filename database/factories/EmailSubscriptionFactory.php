<?php

namespace Database\Factories;

use App\Models\EmailSubscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmailSubscription> */
class EmailSubscriptionFactory extends Factory
{
    protected $model = EmailSubscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'email' => fake()->safeEmail(),
            'user_id' => null,
            'consented_at' => now(),
            'unsubscribed_at' => null,
            'unsubscribe_token' => Str::random(48),
        ];
    }
}
