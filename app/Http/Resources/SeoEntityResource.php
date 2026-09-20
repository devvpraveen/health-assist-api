<?php

namespace App\Http\Resources;

use App\Models\SeoEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeoEntity
 */
class SeoEntityResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'body' => $this->body,
            'locale' => $this->locale,
            'parent_entity_id' => $this->parent_entity_id,
            'status' => $this->status,
            'author_user_id' => $this->author_user_id,
            'reviewer_user_id' => $this->reviewer_user_id,
            'last_reviewed_at' => $this->last_reviewed_at,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'canonical_path' => $this->canonical_path,
            'schema_type' => $this->schema_type,
            'structured_facts' => $this->structured_facts ?? [],
            'direct_answer' => $this->direct_answer,
            'citations' => $this->citations ?? [],
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'related' => PublicSeoEntitySummaryResource::collection($this->whenLoaded('relatedEntities')),
            'faqs' => PublicSeoFaqResource::collection($this->whenLoaded('faqs')),
            'disclaimer' => config('seo.disclaimer'),
        ];
    }
}
