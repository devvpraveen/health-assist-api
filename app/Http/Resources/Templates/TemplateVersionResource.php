<?php

namespace App\Http\Resources\Templates;

use App\Models\TemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TemplateVersion
 */
class TemplateVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'template_id' => $this->template_id,
            'version' => $this->version,
            'status' => $this->status,
            'label' => $this->label,
            'schema' => $this->schema,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
