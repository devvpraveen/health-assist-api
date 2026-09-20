<?php

namespace App\Http\Requests\Api\V1;

use App\Services\I18n\LanguageCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantLanguageSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->isSuperAdmin() || $user->hasPermission('tenant.languages.manage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'array', 'min:1'],
            'enabled.*' => ['required', 'string', 'max:16'],
            'default' => ['required', 'string', 'max:16'],
            'scopes' => ['sometimes', 'array'],
            ...collect(LanguageCatalog::SCOPES)->mapWithKeys(
                fn (string $scope) => [
                    "scopes.{$scope}" => ['sometimes', 'array'],
                    "scopes.{$scope}.*" => ['string', 'max:16'],
                ]
            )->all(),
        ];
    }
}
