<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return $this->user()?->can('manageItems', $invoice) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(InvoiceItem::TYPES)],
            'description' => ['required', 'string', 'max:500'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price_cents' => ['required', 'integer', 'min:0'],
            'discount_cents' => ['sometimes', 'integer', 'min:0'],
            'tax_rate_bps' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
