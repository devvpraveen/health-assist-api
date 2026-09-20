<?php

namespace App\Http\Requests\Api\V1\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class TrackAnalyticsEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:128'],
            'anonymous_id' => ['required', 'string', 'max:128'],
            'properties' => ['sometimes', 'array', 'max:32'],
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
        ];
    }
}
