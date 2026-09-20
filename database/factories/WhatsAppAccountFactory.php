<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppAccount>
 */
class WhatsAppAccountFactory extends Factory
{
    protected $model = WhatsAppAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'clinic_id' => null,
            'name' => 'WhatsApp '.fake()->company(),
            'instance_name' => 'instance_'.Str::lower(Str::random(8)),
            'phone_number' => '5511999999999',
            'status' => WhatsAppAccount::STATUS_CONNECTED,
            'webhook_secret' => 'test-webhook-secret',
            'settings' => null,
            'is_active' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
