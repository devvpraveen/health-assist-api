<?php

namespace App\Http\Resources;

use App\Models\EmailSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmailSubscription */
class EmailSubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'email' => $this->email,
            'user_id' => $this->user_id,
            'consented_at' => $this->consented_at,
            'unsubscribed_at' => $this->unsubscribed_at,
            'created_at' => $this->created_at,
        ];
    }
}
