<?php

namespace App\Actions\HealthGuide;

use App\Actions\Appointments\BookAppointmentAction;
use App\Models\Appointment;
use App\Models\HealthGuideConversation;
use App\Models\HealthGuideMessage;
use App\Models\SafetyAssessment;
use App\Services\AuditLogger;
use App\Services\AI\Learning\LearningEngine;
use App\Services\Safety\SafetyEngine;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class AssistBookingAction
{
    public function __construct(
        private BookAppointmentAction $bookAppointmentAction,
        private SafetyEngine $safetyEngine,
        private AuditLogger $auditLogger,
        private LearningEngine $learningEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{appointment: Appointment, conversation: HealthGuideConversation, disclaimer: string}
     */
    public function handle(HealthGuideConversation $conversation, array $data): array
    {
        if (! ($data['confirm'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm' => ['Explicit confirm:true is required to book via Health Guide.'],
            ]);
        }

        if ($conversation->status === HealthGuideConversation::STATUS_ESCALATED
            || $conversation->safety_level === SafetyAssessment::LEVEL_EMERGENCY) {
            throw ValidationException::withMessages([
                'conversation' => ['Booking is blocked while the conversation is in emergency escalation.'],
            ]);
        }

        $tenantId = TenantContext::id() ?? $conversation->tenant_id;
        $state = $conversation->structured_state ?? [];
        $lastUser = $conversation->messages()->where('role', 'user')->latest('id')->first();
        $text = $lastUser?->content ?? ($state['complaint'] ?? 'booking');

        $safety = $this->safetyEngine->assess((string) $text, $state, [
            'persist' => false,
            'conversation_id' => $conversation->id,
            'patient_id' => $conversation->patient_id,
            'tenant_id' => $tenantId,
        ]);

        if ($safety->level === SafetyAssessment::LEVEL_EMERGENCY || ! $safety->allowsBooking()) {
            throw ValidationException::withMessages([
                'safety' => ['Booking refused due to emergency safety assessment.'],
            ]);
        }

        $patientId = $data['patient_id'] ?? $conversation->patient_id;
        if ($patientId === null) {
            throw ValidationException::withMessages([
                'patient_id' => ['A patient_id is required to book an appointment.'],
            ]);
        }

        $appointment = $this->bookAppointmentAction->handle([
            'patient_id' => $patientId,
            'provider_id' => $data['provider_id'],
            'clinic_id' => $data['clinic_id'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'service_id' => $data['service_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'reason' => $data['reason'] ?? ($state['complaint'] ?? 'Health Guide assisted booking'),
            'notes' => $data['notes'] ?? null,
            'meta' => array_merge($data['meta'] ?? [], [
                'source' => 'health_guide',
                'conversation_uuid' => $conversation->uuid,
            ]),
        ]);

        HealthGuideMessage::query()->create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $conversation->tenant_id,
            'role' => HealthGuideMessage::ROLE_SYSTEM,
            'content' => 'Appointment booked via Health Guide assist.',
            'meta' => [
                'type' => 'booking',
                'appointment_id' => $appointment->id,
                'appointment_uuid' => $appointment->uuid,
            ],
        ]);

        $this->auditLogger->log('health_guide.booking.assisted', $conversation, [
            'conversation_uuid' => $conversation->uuid,
            'appointment_uuid' => $appointment->uuid,
            'provider_id' => $appointment->provider_id,
        ]);

        $this->learningEngine->recordSignal(
            tenantId: (int) $conversation->tenant_id,
            agent: 'health_guide',
            signalType: 'appointment_booked',
            source: 'patient',
            actorUserId: $conversation->user_id,
            patientId: $appointment->patient_id,
            subject: $appointment,
            payload: [
                'provider_id' => $appointment->provider_id,
                'tool_chain' => ['search_provider', 'check_availability', 'book_appointment'],
            ],
        );

        $this->learningEngine->recordSignal(
            tenantId: (int) $conversation->tenant_id,
            agent: 'provider_recommendation',
            signalType: 'provider_selected',
            source: 'patient',
            actorUserId: $conversation->user_id,
            patientId: $appointment->patient_id,
            subject: $appointment,
            payload: [
                'provider_id' => $appointment->provider_id,
                'tool_chain' => ['search_provider', 'book_appointment'],
            ],
        );

        return [
            'appointment' => $appointment,
            'conversation' => $conversation->fresh(['messages', 'patient']),
            'disclaimer' => (string) config('health_guide.disclaimer'),
        ];
    }
}
