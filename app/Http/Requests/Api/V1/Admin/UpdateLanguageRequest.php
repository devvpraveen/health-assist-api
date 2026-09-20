<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Language $language */
        $language = $this->route('language');

        return $this->user()?->can('update', $language) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'native_name' => ['sometimes', 'string', 'max:255'],
            'is_rtl' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
