<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\Experiment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExperimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Experiment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(Experiment::STATUSES)],
            'variants' => ['sometimes', 'array', 'min:1'],
            'variants.*.key' => ['required', 'string', 'max:64'],
            'variants.*.weight' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'variants.*.payload' => ['nullable', 'array'],
        ];
    }
}
