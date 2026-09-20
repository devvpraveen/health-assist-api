<?php

namespace App\Http\Resources;

use App\Models\MarketingLead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarketingLead */
class MarketingLeadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'source' => $this->source,
            'campaign' => $this->campaign,
            'provider_interest' => $this->provider_interest,
            'status' => $this->status,
            'appointment_id' => $this->appointment_id,
            'converted_at' => $this->converted_at,
            'attribution' => $this->attribution,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
