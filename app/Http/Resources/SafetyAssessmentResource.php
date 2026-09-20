<?php

namespace App\Http\Resources;

use App\Models\SafetyAssessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SafetyAssessment
 */
class SafetyAssessmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'conversation_id' => $this->conversation_id,
            'patient_id' => $this->patient_id,
            'input_category' => $this->input_category,
            'input_redacted' => $this->input_redacted,
            'level' => $this->level,
            'matched_rule_codes' => $this->matched_rule_codes,
            'action' => $this->action,
            'rule_version_snapshot' => $this->rule_version_snapshot,
            'model_hint' => $this->model_hint,
            'created_at' => $this->created_at,
        ];
    }
}
