<?php

namespace App\Http\Requests\Api\V1\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class CaptureReferralRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:64'],
            'anonymous_id' => ['required', 'string', 'max:128'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
