<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [PatientDocument::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', PatientDocument::ALLOWED_MIMES),
                'max:'.PatientDocument::MAX_SIZE_KB,
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'health_record_id' => [
                'nullable',
                'integer',
                Rule::exists('health_records', 'id')->where(function ($query): void {
                    /** @var Patient $patient */
                    $patient = $this->route('patient');
                    $query->where('patient_id', $patient->id);
                }),
            ],
        ];
    }
}
