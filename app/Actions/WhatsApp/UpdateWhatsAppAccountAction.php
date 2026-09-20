<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class UpdateWhatsAppAccountAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(WhatsAppAccount $account, array $data): WhatsAppAccount
    {
        $account->fill(collect($data)->only([
            'clinic_id',
            'name',
            'instance_name',
            'phone_number',
            'status',
            'webhook_secret',
            'settings',
            'is_active',
        ])->all());

        if ($account->isDirty('instance_name')) {
            $exists = WhatsAppAccount::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('instance_name', $account->instance_name)
                ->where('id', '!=', $account->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'instance_name' => ['Instance name already in use for this tenant.'],
                ]);
            }
        }

        $account->save();

        $this->auditLogger->log('whatsapp.account.updated', $account, [
            'account_uuid' => $account->uuid,
        ]);

        return $account->fresh();
    }
}
