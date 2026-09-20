<?php

namespace App\Http\Resources\Modules;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlatformModule */
class PlatformModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'key' => $this->key,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'version' => $this->version,
            'status' => $this->status,
            'dependencies' => $this->dependencies ?? [],
            'optional_dependencies' => $this->optional_dependencies ?? [],
            'conflicts' => $this->conflicts ?? [],
            'capabilities' => $this->capabilities ?? [],
            'metadata' => $this->metadata,
        ];
    }
}
