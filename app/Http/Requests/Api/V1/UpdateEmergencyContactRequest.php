<?php

namespace App\Http\Requests\Api\V1;

use App\Models\PatientEmergencyContact;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PatientEmergencyContact $contact */
        $contact = $this->route('emergency_contact');

        return $this->user()?->can('update', $contact) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'relationship' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
