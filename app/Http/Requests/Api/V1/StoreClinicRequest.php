<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Clinic;
use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Clinic::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'organization_id' => [
                'required',
                'integer',
                Rule::exists('organizations', 'id')->where('tenant_id', $tenantId),
            ],
            'primary_branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('clinics', 'slug')->where('tenant_id', $tenantId),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in(Clinic::TYPES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_public' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(Clinic::STATUSES)],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:200'],
            'meta' => ['nullable', 'array'],
            'default_locale' => ['sometimes', 'string', 'max:16'],
            'supported_locales' => ['nullable', 'array'],
            'supported_locales.*' => ['string', 'max:16'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var LanguageCatalog $catalog */
            $catalog = app(LanguageCatalog::class);
            $tenantId = TenantContext::id();

            if ($this->filled('default_locale')) {
                $code = $this->string('default_locale')->toString();
                if (! $catalog->isAllowed($code, 'clinic_app', $tenantId)) {
                    $validator->errors()->add('default_locale', "Language [{$code}] is not enabled for scope [clinic_app].");
                }
            }

            $supported = $this->input('supported_locales');
            if (is_array($supported)) {
                foreach ($supported as $index => $code) {
                    if (! is_string($code) || ! $catalog->isAllowed($code, 'clinic_app', $tenantId)) {
                        $validator->errors()->add("supported_locales.{$index}", "Language [{$code}] is not enabled for scope [clinic_app].");
                    }
                }
            }
        });
    }
}
