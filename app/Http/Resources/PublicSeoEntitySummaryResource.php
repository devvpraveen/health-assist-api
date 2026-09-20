<?php

namespace App\Http\Resources;

use App\Models\SeoEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeoEntity
 */
class PublicSeoEntitySummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $relation = null;
        if (isset($this->pivot) && isset($this->pivot->relation)) {
            $relation = $this->pivot->relation;
        }

        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'locale' => $this->locale,
            'summary' => $this->summary,
            'relation' => $relation,
        ];
    }
}
