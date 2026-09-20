<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'patient_id' => $this->patient_id,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'method' => $this->method,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'status' => $this->status,
            'idempotency_key' => $this->idempotency_key,
            'paid_at' => $this->paid_at,
            'recorded_by_user_id' => $this->recorded_by_user_id,
            'meta' => $this->meta,
            'receipt' => ReceiptResource::make($this->whenLoaded('receipt')),
            'invoice' => InvoiceResource::make($this->whenLoaded('invoice')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
