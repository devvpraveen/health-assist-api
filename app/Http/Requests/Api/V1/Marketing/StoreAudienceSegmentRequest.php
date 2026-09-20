<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\AudienceSegment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAudienceSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AudienceSegment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'definition' => ['sometimes', 'array'],
            'definition.inactive_days' => ['sometimes', 'integer', 'min:1'],
            'definition.new_user' => ['sometimes', 'boolean'],
            'definition.new_user_days' => ['sometimes', 'integer', 'min:1'],
            'definition.appointment_upcoming' => ['sometimes', 'boolean'],
        ];
    }
}
