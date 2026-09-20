<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [PatientEmergencyContact::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
