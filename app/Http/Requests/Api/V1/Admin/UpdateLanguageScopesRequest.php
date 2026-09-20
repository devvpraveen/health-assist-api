<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Language;
use App\Services\I18n\LanguageCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageScopesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Language $language */
        $language = $this->route('language');

        return $this->user()?->can('manageScopes', $language) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*.scope' => ['required', 'string', Rule::in(LanguageCatalog::SCOPES)],
            'scopes.*.is_enabled' => ['required', 'boolean'],
        ];
    }
}
