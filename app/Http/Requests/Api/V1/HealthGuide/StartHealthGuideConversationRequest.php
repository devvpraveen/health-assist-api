<?php

namespace App\Http\Requests\Api\V1\HealthGuide;

use Illuminate\Foundation\Http\FormRequest;

class StartHealthGuideConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'nullable', 'integer', 'exists:patients,id'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:16'],
        ];
    }
}
