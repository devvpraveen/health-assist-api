<?php

namespace App\Http\Requests\Api\V1\Ai;

use App\Rules\RejectAiSecretInConfig;
use Illuminate\Foundation\Http\FormRequest;

class RunAiAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->isSuperAdmin()
                || $this->user()->hasPermission('ai.run')
                || $this->user()->hasPermission('ai.manage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feature' => ['sometimes', 'nullable', 'string', 'max:100'],
            'input' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'messages' => ['sometimes', 'nullable', 'array', 'max:50'],
            'messages.*.role' => ['required_with:messages', 'string', 'in:system,user,assistant'],
            'messages.*.content' => ['required_with:messages', 'string', 'max:10000'],
            'task_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model_config' => ['sometimes', 'nullable', 'array', new RejectAiSecretInConfig],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasModelOverride = $this->filled('model') || $this->filled('model_config');
            if (! $hasModelOverride) {
                return;
            }

            $user = $this->user();
            $canManage = $user !== null
                && ($user->isSuperAdmin() || $user->hasPermission('ai.manage'));

            if (! $canManage) {
                $validator->errors()->add('model', 'Model overrides require ai.manage permission.');
            }
        });
    }
}
