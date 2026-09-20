<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateWhatsAppAccountAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): WhatsAppAccount
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            throw ValidationException::withMessages(['tenant' => ['Tenant context is required.']]);
        }

        $account = WhatsAppAccount::query()->create([
            'tenant_id' => $tenantId,
            'clinic_id' => $data['clinic_id'] ?? null,
            'name' => $data['name'],
            'instance_name' => $data['instance_name'],
            'phone_number' => $data['phone_number'] ?? null,
            'status' => $data['status'] ?? WhatsAppAccount::STATUS_DISCONNECTED,
            'webhook_secret' => $data['webhook_secret'] ?? Str::random(40),
            'settings' => $data['settings'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditLogger->log('whatsapp.account.created', $account, [
            'account_uuid' => $account->uuid,
            'instance_name' => $account->instance_name,
        ]);

        return $account;
    }
}
