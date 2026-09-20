<?php

namespace App\Http\Resources\Ai;

use App\Models\AiModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiModel
 */
class AiModelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultVersion = $this->versions->firstWhere('is_default', true)
            ?? $this->versions->first();

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'task_types' => $this->task_types,
            'config' => $this->config,
            'is_custom' => (bool) $this->is_custom,
            'external_model_id' => $this->external_model_id,
            'is_active' => $this->is_active,
            'provider' => [
                'id' => $this->provider?->id,
                'key' => $this->provider?->key,
                'name' => $this->provider?->name,
                'driver' => $this->provider?->driver,
            ],
            'default_version' => $defaultVersion?->version,
            'versions' => $this->whenLoaded('versions', fn () => $this->versions->map(fn ($version) => [
                'id' => $version->id,
                'version' => $version->version,
                'is_default' => (bool) $version->is_default,
                'config' => $version->config,
            ])->values()->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
