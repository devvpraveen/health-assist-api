<?php

namespace App\Http\Resources;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Refund
 */
class RefundResource extends JsonResource
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
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'patient_id' => $this->patient_id,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'processed_at' => $this->processed_at,
            'recorded_by_user_id' => $this->recorded_by_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
