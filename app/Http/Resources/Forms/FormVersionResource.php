<?php

namespace App\Http\Resources\Forms;

use App\Models\FormVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FormVersion
 */
class FormVersionResource extends JsonResource
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
            'version' => $this->version,
            'status' => $this->status,
            'label' => $this->label,
            'schema' => $this->schema,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
