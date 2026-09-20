<?php

namespace App\Http\Resources\Forms;

use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FormSubmission
 */
class FormSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'form_definition_id' => $this->form_definition_id,
            'form_version_id' => $this->form_version_id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'payload' => $this->payload,
            'meta' => $this->meta,
            'submitted_by_user_id' => $this->submitted_by_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
