<?php

namespace App\Http\Resources;

use App\Models\AttributionTouch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttributionTouch */
class AttributionTouchResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'anonymous_id' => $this->anonymous_id,
            'user_id' => $this->user_id,
            'campaign' => $this->campaign,
            'source' => $this->source,
            'medium' => $this->medium,
            'content' => $this->content,
            'term' => $this->term,
            'referrer' => $this->referrer,
            'landing_path' => $this->landing_path,
            'captured_at' => $this->captured_at,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
