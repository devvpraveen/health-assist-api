<?php

namespace App\Http\Resources;

use App\Models\ExperimentVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExperimentVariant */
class ExperimentVariantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'weight' => $this->weight,
            'payload' => $this->payload,
        ];
    }
}
