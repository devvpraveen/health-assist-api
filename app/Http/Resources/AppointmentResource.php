<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'patient_id' => $this->patient_id,
            'provider_id' => $this->provider_id,
            'clinic_id' => $this->clinic_id,
            'branch_id' => $this->branch_id,
            'service_id' => $this->service_id,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'booked_by_user_id' => $this->booked_by_user_id,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'rescheduled_from_appointment_id' => $this->rescheduled_from_appointment_id,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'queue_number' => $this->queue_number,
            'reminder_sent_at' => $this->reminder_sent_at?->toIso8601String(),
            'meta' => $this->meta,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'clinic' => new ClinicResource($this->whenLoaded('clinic')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'reminders' => $this->whenLoaded('reminders', function () {
                return $this->reminders->map(fn ($reminder) => [
                    'id' => $reminder->id,
                    'uuid' => $reminder->uuid,
                    'channel' => $reminder->channel,
                    'scheduled_for' => $reminder->scheduled_for?->toIso8601String(),
                    'sent_at' => $reminder->sent_at?->toIso8601String(),
                    'status' => $reminder->status,
                ]);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
