<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('organization')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $organization->tenant_id))
                    ->ignore($organization->id),
            ],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
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
        ];
    }
}
