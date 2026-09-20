<?php

namespace App\Http\Resources\Ai;

use App\Models\AiPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiPrompt
 */
class AiPromptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $active = $this->relationLoaded('activeVersionRelation')
            ? $this->activeVersionRelation
            : ($this->relationLoaded('versions') ? $this->activeVersion() : null);

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'active_version' => $active ? AiPromptVersionResource::make($active) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
