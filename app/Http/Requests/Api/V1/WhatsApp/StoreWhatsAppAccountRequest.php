<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhatsAppAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WhatsAppAccount::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instance_name' => ['required', 'string', 'max:255'],
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(WhatsAppAccount::STATUSES)],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
