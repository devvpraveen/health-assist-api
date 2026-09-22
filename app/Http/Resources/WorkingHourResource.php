<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingHourResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'clinic_id' => $this->clinic_id,
            'provider_id' => $this->provider_id,
            'day_of_week' => $this->day_of_week,
            'opens_at' => substr((string) $this->opens_at, 0, 5),
            'closes_at' => substr((string) $this->closes_at, 0, 5),
            'break_starts_at' => $this->break_starts_at ? substr((string) $this->break_starts_at, 0, 5) : null,
            'break_ends_at' => $this->break_ends_at ? substr((string) $this->break_ends_at, 0, 5) : null,
            'shift_index' => $this->shift_index,
            'is_closed' => (bool) $this->is_closed,
            'label' => $this->label,
        ];
    }
}
