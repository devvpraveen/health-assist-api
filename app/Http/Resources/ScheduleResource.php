<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Schedule
 */
class ScheduleResource extends JsonResource
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
            'provider_id' => $this->provider_id,
            'branch_id' => $this->branch_id,
            'clinic_id' => $this->clinic_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->formatTime($this->start_time),
            'end_time' => $this->formatTime($this->end_time),
            'slot_duration_minutes' => $this->slot_duration_minutes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }
}
