<?php

namespace App\Http\Requests\Api\V1\Wellness;

use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPatientWellnessPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('update', [PatientWellnessPreference::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'interests' => ['required', 'array'],
            'interests.*' => ['string', Rule::exists('wellness_categories', 'slug')],
            'goals' => ['nullable', 'array'],
            'goals.*' => ['string', 'max:100'],
            'excluded_tags' => ['nullable', 'array'],
            'excluded_tags.*' => ['string', 'max:100'],
            'reminder_opt_in' => ['sometimes', 'boolean'],
        ];
    }
}
