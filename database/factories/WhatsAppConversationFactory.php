<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppConversation>
 */
class WhatsAppConversationFactory extends Factory
{
    protected $model = WhatsAppConversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '5511987654321';

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'account_id' => WhatsAppAccount::factory(),
            'patient_id' => null,
            'remote_phone' => $phone,
            'remote_jid' => $phone.'@s.whatsapp.net',
            'state' => WhatsAppConversation::STATE_AI,
            'health_guide_conversation_id' => null,
            'handoff_summary' => null,
            'last_message_at' => null,
        ];
    }

    public function forAccount(WhatsAppAccount $account): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $account->tenant_id,
            'account_id' => $account->id,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
