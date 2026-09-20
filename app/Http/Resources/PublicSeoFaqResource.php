<?php

namespace App\Http\Resources;

use App\Models\SeoFaq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeoFaq
 */
class PublicSeoFaqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'question' => $this->question,
            'answer' => $this->answer,
            'locale' => $this->locale,
            'sort_order' => $this->sort_order,
            'entity_uuid' => $this->whenLoaded('entity', fn () => $this->entity?->uuid),
            'entity_slug' => $this->whenLoaded('entity', fn () => $this->entity?->slug),
            'entity_type' => $this->whenLoaded('entity', fn () => $this->entity?->type),
        ];
    }
}
