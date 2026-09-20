<?php

namespace App\Http\Resources;

use App\Models\MedicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MedicationSchedule
 */
class MedicationScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'medication_id' => $this->medication_id,
            'time_of_day' => $this->time_of_day,
            'timezone' => $this->timezone,
            'days_of_week' => $this->days_of_week,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
