<?php

namespace App\Http\Requests\Api\V1\Mobile;

use App\Services\Mobile\MobileExperienceBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMobileAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_type' => ['required', 'string', Rule::in(MobileExperienceBuilder::ACCOUNT_TYPES)],
            'professional_type' => [
                'nullable',
                'string',
                Rule::in(MobileExperienceBuilder::PROFESSIONAL_TYPES),
                Rule::requiredIf(fn () => $this->input('account_type') === 'healthcare_professional'),
            ],
            'active_workspace' => ['nullable', 'string', 'max:128'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
