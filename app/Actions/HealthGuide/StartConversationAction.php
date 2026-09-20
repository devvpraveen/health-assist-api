<?php

namespace App\Actions\HealthGuide;

use App\Models\HealthGuideConversation;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StartConversationAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{patient_id?: int|null, locale?: string|null}  $data
     */
    public function handle(array $data): HealthGuideConversation
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant' => ['Tenant context is required.'],
            ]);
        }

        $patientId = $data['patient_id'] ?? null;
        if ($patientId !== null) {
            $patient = Patient::query()->findOrFail($patientId);
            if ($patient->tenant_id !== $tenantId) {
                throw ValidationException::withMessages([
                    'patient_id' => ['Patient must belong to the current tenant.'],
                ]);
            }
        }

        $conversation = HealthGuideConversation::query()->create([
            'tenant_id' => $tenantId,
            'patient_id' => $patientId,
            'user_id' => Auth::id(),
            'status' => HealthGuideConversation::STATUS_ACTIVE,
            'locale' => $data['locale'] ?? null,
            'structured_state' => [
                'intent' => null,
                'complaint' => null,
                'duration' => null,
                'severity' => null,
                'care_category' => null,
                'urgency' => null,
                'missing_fields' => ['complaint', 'duration', 'severity'],
            ],
        ]);

        $this->auditLogger->log('health_guide.conversation.started', $conversation, [
            'conversation_uuid' => $conversation->uuid,
            'patient_id' => $patientId,
        ]);

        return $conversation->fresh(['messages', 'patient']);
    }
}
