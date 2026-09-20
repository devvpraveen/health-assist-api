<?php

namespace App\Http\Requests\Api\V1\Medications;

use App\Models\Medication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Medication $medication */
        $medication = $this->route('medication');

        return $this->user()?->can('update', $medication) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_id' => ['sometimes', 'nullable', 'integer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'dosage' => ['sometimes', 'string', 'max:255'],
            'frequency_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'route' => ['sometimes', 'nullable', 'string', 'max:100'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(Medication::STATUSES)],
        ];
    }
}
