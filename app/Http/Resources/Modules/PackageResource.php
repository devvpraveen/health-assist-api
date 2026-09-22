<?php

namespace App\Http\Resources\Modules;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Package */
class PackageResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'price_cents' => (int) $this->price_cents,
            'currency' => $this->currency ?: 'INR',
            'validity_days' => $this->validity_days,
            'metadata' => $this->metadata,
            'modules' => $this->whenLoaded('modules', function () {
                return $this->modules->map(fn ($m) => [
                    'key' => $m->key,
                    'name' => $m->name,
                    'inclusion' => $m->pivot->inclusion ?? 'included',
                ]);
            }),
            'entitlements' => $this->whenLoaded('entitlements', function () {
                return $this->entitlements->mapWithKeys(fn ($e) => [$e->key => $e->enabled]);
            }),
            'limits' => $this->whenLoaded('limits', function () {
                return $this->limits->mapWithKeys(fn ($l) => [$l->key => [
                    'value' => $l->value,
                    'period' => $l->period,
                ]]);
            }),
        ];
    }
}
