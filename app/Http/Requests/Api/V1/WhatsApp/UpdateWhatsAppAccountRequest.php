<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWhatsAppAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WhatsAppAccount $account */
        $account = $this->route('account');

        return $this->user()?->can('update', $account) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'instance_name' => ['sometimes', 'string', 'max:255'],
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(WhatsAppAccount::STATUSES)],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
