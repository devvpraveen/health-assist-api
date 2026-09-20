<?php

namespace App\Http\Requests\Api\V1\Ai;

use App\Models\AiPromptVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiPromptVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->isSuperAdmin() || $this->user()->hasPermission('ai.manage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'system_prompt' => ['required', 'string', 'max:50000'],
            'template' => ['sometimes', 'nullable', 'string', 'max:50000'],
            'status' => ['sometimes', 'string', Rule::in([
                AiPromptVersion::STATUS_DRAFT,
                AiPromptVersion::STATUS_ACTIVE,
            ])],
        ];
    }
}
