<?php

namespace App\Http\Requests\Api\V1\HealthGuide;

use Illuminate\Foundation\Http\FormRequest;

class SendHealthGuideMessageRequest extends FormRequest
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
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
