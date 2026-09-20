<?php

namespace App\Http\Resources;

use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReferralCode */
class ReferralCodeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'code' => $this->code,
            'owner_user_id' => $this->owner_user_id,
            'owner_patient_id' => $this->owner_patient_id,
            'campaign' => $this->campaign,
            'is_active' => $this->is_active,
            'max_uses' => $this->max_uses,
            'uses_count' => $this->uses_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
