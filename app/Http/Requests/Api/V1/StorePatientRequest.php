<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Patient;
use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Patient::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['sometimes', 'string', 'max:10'],
            'communication_preferences' => ['nullable', 'array'],
            'communication_preferences.email' => ['sometimes', 'boolean'],
            'communication_preferences.sms' => ['sometimes', 'boolean'],
            'communication_preferences.whatsapp' => ['sometimes', 'boolean'],
            'communication_preferences.push' => ['sometimes', 'boolean'],
            'consent_status' => ['sometimes', 'string', Rule::in(Patient::CONSENT_STATUSES)],
            'consent_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(Patient::STATUSES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('preferred_language')) {
                return;
            }

            /** @var LanguageCatalog $catalog */
            $catalog = app(LanguageCatalog::class);
            $code = $this->string('preferred_language')->toString();

            if (! $catalog->isAllowed($code, 'patient_app', TenantContext::id())) {
                $validator->errors()->add(
                    'preferred_language',
                    "Language [{$code}] is not enabled for scope [patient_app].",
                );
            }
        });
    }
}
