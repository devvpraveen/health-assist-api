<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'clinic_id' => $this->clinic_id,
            'appointment_id' => $this->appointment_id,
            'issued_by_user_id' => $this->issued_by_user_id,
            'number' => $this->number,
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,
            'amount_paid_cents' => $this->amount_paid_cents,
            'amount_due_cents' => $this->amount_due_cents,
            'issued_at' => $this->issued_at,
            'due_at' => $this->due_at,
            'paid_at' => $this->paid_at,
            'cancelled_at' => $this->cancelled_at,
            'notes' => $this->notes,
            'meta' => $this->meta,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
