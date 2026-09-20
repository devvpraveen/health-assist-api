<?php

namespace App\Http\Resources;

use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Referral */
class ReferralResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'referrer_code_id' => $this->referrer_code_id,
            'referred_anonymous_id' => $this->referred_anonymous_id,
            'referred_user_id' => $this->referred_user_id,
            'referred_patient_id' => $this->referred_patient_id,
            'status' => $this->status,
            'converted_at' => $this->converted_at,
            'meta' => $this->meta,
            'rewards' => ReferralRewardResource::collection($this->whenLoaded('rewards')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
