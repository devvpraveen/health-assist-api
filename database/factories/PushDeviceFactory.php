<?php

namespace Database\Factories;

use App\Models\PushDevice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PushDevice> */
class PushDeviceFactory extends Factory
{
    protected $model = PushDevice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'token' => Str::random(64),
            'platform' => 'ios',
            'last_seen_at' => now(),
        ];
    }
}
