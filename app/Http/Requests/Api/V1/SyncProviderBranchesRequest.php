<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;

class SyncProviderBranchesRequest extends FormRequest
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
            'branch_ids' => ['required', 'array'],
            'branch_ids.*' => ['integer', 'distinct'],
        ];
    }
}
