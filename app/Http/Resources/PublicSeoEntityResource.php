<?php

namespace App\Http\Resources;

use App\Models\SeoEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeoEntity
 */
class PublicSeoEntityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'body' => $this->body,
            'locale' => $this->locale,
            'seo_title' => $this->seo_title ?: $this->title.' | Health Assist',
            'seo_description' => $this->seo_description ?: $this->summary,
            'canonical_path' => $this->canonical_path,
            'schema_type' => $this->schema_type,
            'structured_facts' => $this->structured_facts ?? [],
            'direct_answer' => $this->direct_answer,
            'citations' => $this->citations ?? [],
            'last_reviewed_at' => $this->last_reviewed_at,
            'published_at' => $this->published_at,
            'related' => PublicSeoEntitySummaryResource::collection($this->whenLoaded('relatedEntities')),
            'faqs' => PublicSeoFaqResource::collection($this->whenLoaded('faqs')),
            'disclaimer' => config('seo.disclaimer'),
            'not_a_diagnosis' => true,
        ];
    }
}
