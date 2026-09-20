<?php

namespace App\Http\Resources;

use App\Models\WellnessContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WellnessContent
 */
class WellnessContentResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => new WellnessCategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'body' => $this->body,
            'locale' => $this->locale,
            'is_clinical_advice' => $this->is_clinical_advice,
            'status' => $this->status,
            'author_user_id' => $this->author_user_id,
            'published_at' => $this->published_at,
            'personalization_tags' => $this->personalization_tags,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'disclaimer' => config('wellness.disclaimer'),
        ];
    }
}
