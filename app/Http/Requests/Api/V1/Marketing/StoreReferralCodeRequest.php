<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;

class StoreReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReferralCode::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:64', 'unique:referral_codes,code'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'owner_patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
