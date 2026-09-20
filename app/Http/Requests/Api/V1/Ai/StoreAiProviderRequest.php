<?php

namespace App\Http\Requests\Api\V1\Ai;

use App\Rules\RejectAiSecretInConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiProviderRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:ai_providers,key'],
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', Rule::in((array) config('ai.allowed_drivers', []))],
            'config' => ['sometimes', 'nullable', 'array', new RejectAiSecretInConfig],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
