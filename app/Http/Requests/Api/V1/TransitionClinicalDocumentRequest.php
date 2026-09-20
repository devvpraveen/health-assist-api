<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionClinicalDocumentRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(ClinicalDocumentWorkflow::STATUSES)],
        ];
    }
}
