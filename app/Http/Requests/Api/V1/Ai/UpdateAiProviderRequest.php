<?php

namespace App\Http\Requests\Api\V1\Ai;

use App\Rules\RejectAiSecretInConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiProviderRequest extends FormRequest
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
        $providerId = $this->route('provider')?->id ?? $this->route('provider');

        return [
            'key' => [
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('ai_providers', 'key')->ignore($providerId),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'driver' => ['sometimes', 'string', Rule::in((array) config('ai.allowed_drivers', []))],
            'config' => ['sometimes', 'nullable', 'array', new RejectAiSecretInConfig],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
