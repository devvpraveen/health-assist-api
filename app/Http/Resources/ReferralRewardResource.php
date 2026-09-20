<?php

namespace App\Http\Resources;

use App\Models\ReferralReward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReferralReward */
class ReferralRewardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'referral_id' => $this->referral_id,
            'type' => $this->type,
            'status' => $this->status,
            'amount_cents' => $this->amount_cents,
        ];
    }
}
