<?php

namespace App\Http\Requests\Api\V1\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class CaptureAttributionRequest extends FormRequest
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
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'medium' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:255'],
            'term' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'landing_path' => ['nullable', 'string', 'max:2048'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
