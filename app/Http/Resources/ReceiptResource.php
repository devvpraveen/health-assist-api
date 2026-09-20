<?php

namespace App\Http\Resources;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Receipt
 */
class ReceiptResource extends JsonResource
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
            'number' => $this->number,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'issued_at' => $this->issued_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
