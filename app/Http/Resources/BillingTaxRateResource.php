<?php

namespace App\Http\Resources;

use App\Models\BillingTaxRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BillingTaxRate
 */
class BillingTaxRateResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'rate_bps' => $this->rate_bps,
            'is_inclusive' => $this->is_inclusive,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
