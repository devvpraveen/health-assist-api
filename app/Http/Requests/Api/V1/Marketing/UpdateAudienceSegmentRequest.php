<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\AudienceSegment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAudienceSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AudienceSegment $segment */
        $segment = $this->route('segment');

        return $this->user()?->can('update', $segment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:128'],
            'name' => ['sometimes', 'string', 'max:255'],
            'definition' => ['sometimes', 'array'],
            'definition.inactive_days' => ['sometimes', 'integer', 'min:1'],
            'definition.new_user' => ['sometimes', 'boolean'],
            'definition.new_user_days' => ['sometimes', 'integer', 'min:1'],
            'definition.appointment_upcoming' => ['sometimes', 'boolean'],
        ];
    }
}
