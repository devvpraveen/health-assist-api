<?php

namespace App\Http\Resources;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceItem
 */
class InvoiceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'tenant_id' => $this->tenant_id,
            'type' => $this->type,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'discount_cents' => $this->discount_cents,
            'tax_rate_bps' => $this->tax_rate_bps,
            'tax_cents' => $this->tax_cents,
            'line_total_cents' => $this->line_total_cents,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
