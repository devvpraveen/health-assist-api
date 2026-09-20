<?php

namespace App\Http\Requests\Api\V1\HealthGuide;

use Illuminate\Foundation\Http\FormRequest;

class AssessSafetyRequest extends FormRequest
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
            'text' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
