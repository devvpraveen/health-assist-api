<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('manageHealthProfile', $patient) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'medical_history' => ['sometimes', 'nullable', 'string'],
            'conditions' => ['sometimes', 'nullable', 'array'],
            'allergies' => ['sometimes', 'nullable', 'array'],
            'medications' => ['sometimes', 'nullable', 'array'],
            'previous_treatments' => ['sometimes', 'nullable', 'string'],
            'surgeries' => ['sometimes', 'nullable', 'array'],
            'family_history' => ['sometimes', 'nullable', 'string'],
            'lifestyle' => ['sometimes', 'nullable', 'string'],
            'emergency_information' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
