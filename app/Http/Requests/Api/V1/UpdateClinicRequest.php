<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Clinic;
use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Clinic $clinic */
        $clinic = $this->route('clinic');

        return $this->user()?->can('update', $clinic) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        /** @var Clinic $clinic */
        $clinic = $this->route('clinic');

        return [
            'organization_id' => [
                'sometimes',
                'integer',
                Rule::exists('organizations', 'id')->where('tenant_id', $tenantId),
            ],
            'primary_branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('clinics', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->ignore($clinic->id),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in(Clinic::TYPES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
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
            'profile' => ['sometimes', 'array'],
            'profile.tagline' => ['nullable', 'string', 'max:255'],
            'profile.hero_headline' => ['nullable', 'string', 'max:255'],
            'profile.hero_subheadline' => ['nullable', 'string'],
            'profile.hero_image_url' => ['nullable', 'string', 'max:500'],
            'profile.about' => ['nullable', 'string'],
            'profile.booking_benefits' => ['nullable', 'array'],
            'profile.booking_benefits.*' => ['string', 'max:255'],
            'profile.highlights' => ['nullable', 'array'],
            'profile.highlights.*.label' => ['required_with:profile.highlights', 'string', 'max:100'],
            'profile.highlights.*.value' => ['required_with:profile.highlights', 'string', 'max:100'],
            'profile.testimonials' => ['nullable', 'array'],
            'profile.testimonials.*.quote' => ['required_with:profile.testimonials', 'string'],
            'profile.testimonials.*.author' => ['required_with:profile.testimonials', 'string', 'max:120'],
            'profile.testimonials.*.role' => ['nullable', 'string', 'max:120'],
            'profile.gallery' => ['nullable', 'array'],
            'profile.gallery.*' => ['string', 'max:500'],
            'profile.cta_label' => ['nullable', 'string', 'max:100'],
            'profile.cta_href' => ['nullable', 'string', 'max:500'],
            'profile.seo_title' => ['nullable', 'string', 'max:255'],
            'profile.seo_description' => ['nullable', 'string', 'max:500'],
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

            if ($this->exists('supported_locales') && is_array($this->input('supported_locales'))) {
                foreach ($this->input('supported_locales') as $index => $code) {
                    if (! is_string($code) || ! $catalog->isAllowed($code, 'clinic_app', $tenantId)) {
                        $validator->errors()->add("supported_locales.{$index}", "Language [{$code}] is not enabled for scope [clinic_app].");
                    }
                }
            }
        });
    }
}
