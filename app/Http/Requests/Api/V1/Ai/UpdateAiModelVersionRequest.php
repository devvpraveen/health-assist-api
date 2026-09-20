<?php

namespace App\Http\Requests\Api\V1\Ai;

use App\Rules\RejectAiSecretInConfig;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAiModelVersionRequest extends FormRequest
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
            'version' => ['sometimes', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'nullable', 'array', new RejectAiSecretInConfig],
        ];
    }
}
