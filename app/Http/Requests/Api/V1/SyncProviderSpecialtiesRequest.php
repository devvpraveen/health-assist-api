<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;

class SyncProviderSpecialtiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Provider $provider */
        $provider = $this->route('provider');

        return $this->user()?->can('syncRelations', $provider) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'specialty_ids' => ['required', 'array'],
            'specialty_ids.*' => ['integer', 'distinct'],
            'specialties' => ['sometimes', 'array'],
            'specialties.*.id' => ['required_with:specialties', 'integer'],
            'specialties.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
