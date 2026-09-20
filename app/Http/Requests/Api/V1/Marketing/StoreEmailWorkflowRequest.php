<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\EmailWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmailWorkflow::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', 'string', Rule::in(EmailWorkflow::TRIGGERS)],
            'is_active' => ['sometimes', 'boolean'],
            'steps' => ['sometimes', 'array'],
            'steps.*.subject' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.body' => ['required_with:steps', 'string'],
            'steps.*.delay_seconds' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
