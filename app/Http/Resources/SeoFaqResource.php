<?php

namespace App\Http\Resources;

use App\Models\SeoFaq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeoFaq
 */
class SeoFaqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'tenant_key' => $this->tenant_key,
            'entity_id' => $this->entity_id,
            'question' => $this->question,
            'answer' => $this->answer,
            'locale' => $this->locale,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'entity' => new PublicSeoEntitySummaryResource($this->whenLoaded('entity')),
        ];
    }
}
