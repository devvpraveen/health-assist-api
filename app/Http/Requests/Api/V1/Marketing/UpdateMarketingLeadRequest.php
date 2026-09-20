<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\MarketingLead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarketingLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MarketingLead $lead */
        $lead = $this->route('lead');

        return $this->user()?->can('update', $lead) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:255'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'provider_interest' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(MarketingLead::STATUSES)],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'attribution' => ['nullable', 'array'],
            'converted_at' => ['nullable', 'date'],
        ];
    }
}
