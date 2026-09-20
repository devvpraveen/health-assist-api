<?php

namespace App\Http\Resources;

use App\Models\PatientWellnessPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PatientWellnessPreference
 */
class PatientWellnessPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'interests' => $this->interests,
            'goals' => $this->goals,
            'excluded_tags' => $this->excluded_tags,
            'reminder_opt_in' => $this->reminder_opt_in,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
