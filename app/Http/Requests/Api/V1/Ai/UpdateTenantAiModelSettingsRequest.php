<?php

namespace App\Http\Requests\Api\V1\Ai;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantAiModelSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->isSuperAdmin()
                || $user->hasPermission('ai.manage')
                || $user->hasPermission('tenant.ai.manage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_provider' => ['sometimes', 'nullable', 'string', 'max:100'],
            'models' => ['sometimes', 'array'],
            'models.*' => ['array'],
            'models.*.provider' => ['sometimes', 'nullable', 'string', 'max:100'],
            'models.*.model' => ['required', 'string', 'max:255'],
            'models.*.model_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'models.*.config' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
