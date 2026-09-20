<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Language::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:16', 'alpha_dash:ascii', Rule::unique('languages', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'native_name' => ['required', 'string', 'max:255'],
            'is_rtl' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
