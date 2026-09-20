<?php

namespace App\Http\Requests\Api\V1\Ai;

use App\Rules\RejectAiSecretInConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiModelRequest extends FormRequest
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
            'provider_id' => ['sometimes', 'integer', 'exists:ai_providers,id'],
            'provider_key' => ['sometimes', 'string', 'exists:ai_providers,key'],
            'key' => ['sometimes', 'string', 'max:100', 'alpha_dash'],
            'name' => ['sometimes', 'string', 'max:255'],
            'task_types' => ['sometimes', 'array', 'min:1'],
            'task_types.*' => ['string', Rule::in((array) config('ai.allowed_task_types', []))],
            'config' => ['sometimes', 'nullable', 'array', new RejectAiSecretInConfig],
            'external_model_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_custom' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
