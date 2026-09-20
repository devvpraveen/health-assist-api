<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Clinic;
use Illuminate\Foundation\Http\FormRequest;

class SyncClinicSpecialtiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Clinic $clinic */
        $clinic = $this->route('clinic');

        return $this->user()?->can('manageSpecialties', $clinic) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'specialty_ids' => ['required', 'array'],
            'specialty_ids.*' => ['integer', 'distinct'],
        ];
    }
}
