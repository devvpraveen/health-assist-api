<?php

namespace App\Http\Resources;

use App\Models\BillingPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BillingPackage
 */
class BillingPackageResource extends JsonResource
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
            'clinic_id' => $this->clinic_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'session_count' => $this->session_count,
            'validity_days' => $this->validity_days,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
