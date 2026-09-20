<?php

namespace App\Http\Requests\Api\V1\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class AssignExperimentRequest extends FormRequest
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
            'anonymous_id' => ['required', 'string', 'max:128'],
        ];
    }
}
