<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\EmailWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmailWorkflow $workflow */
        $workflow = $this->route('workflow');

        return $this->user()?->can('update', $workflow) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:128'],
            'name' => ['sometimes', 'string', 'max:255'],
            'trigger' => ['sometimes', 'string', Rule::in(EmailWorkflow::TRIGGERS)],
            'is_active' => ['sometimes', 'boolean'],
            'steps' => ['sometimes', 'array'],
            'steps.*.subject' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.body' => ['required_with:steps', 'string'],
            'steps.*.delay_seconds' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
