<?php

namespace App\Http\Requests\Api\V1;

use App\Models\PatientPackage;
use Illuminate\Foundation\Http\FormRequest;

class ConsumePatientPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PatientPackage $patientPackage */
        $patientPackage = $this->route('patient_package');

        return $this->user()?->can('consume', $patientPackage) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sessions' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
