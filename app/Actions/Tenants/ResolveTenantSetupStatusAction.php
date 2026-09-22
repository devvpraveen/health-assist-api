<?php

namespace App\Actions\Tenants;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Models\WorkingHour;
use App\Support\TenantContext;

class ResolveTenantSetupStatusAction
{
    /**
     * @return array{
     *   profile_completion: int,
     *   onboarding_completed: bool,
     *   getting_started_dismissed: bool,
     *   sections: list<array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}>,
     *   missing: list<array{key: string, label: string, href: string}>,
     *   modules: array<string, array{enabled: bool, ready: bool, missing: list<string>}>,
     *   organization: array{id: int|null, name: string|null, org_type: string|null}|null
     * }
     */
    public function handle(?User $user = null, ?Tenant $tenant = null): array
    {
        $tenantId = $tenant?->id ?? $user?->tenant_id ?? TenantContext::id();
        if ($tenantId === null) {
            return [
                'profile_completion' => 0,
                'onboarding_completed' => false,
                'getting_started_dismissed' => false,
                'sections' => [],
                'missing' => [],
                'modules' => [],
                'organization' => null,
            ];
        }

        $organization = Organization::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        $clinic = Clinic::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        $meta = $organization?->meta ?? [];
        $orgType = is_string($meta['org_type'] ?? null) ? $meta['org_type'] : null;

        $sections = [
            $this->sectionBasic($organization),
            $this->sectionContact($organization, $clinic),
            $this->sectionPublic($organization, $clinic),
            $this->sectionHours($tenantId, $clinic),
            $this->sectionServices($tenantId, $clinic),
            $this->sectionBranding($organization, $clinic),
            $this->sectionStaff($tenantId),
            $this->sectionCommunication($organization, $clinic),
        ];

        $totalWeight = array_sum(array_column($sections, 'weight'));
        $earned = 0;
        foreach ($sections as $section) {
            $earned += $section['complete'] ? $section['weight'] : (int) round($section['weight'] * ($section['percent'] / 100));
        }
        $completion = $totalWeight > 0 ? (int) round(($earned / $totalWeight) * 100) : 0;

        $missing = [];
        foreach ($sections as $section) {
            foreach ($section['missing'] as $item) {
                $missing[] = $item;
            }
        }

        return [
            'profile_completion' => min(100, max(0, $completion)),
            'onboarding_completed' => (bool) ($meta['onboarding_completed'] ?? false),
            'getting_started_dismissed' => (bool) ($meta['getting_started_dismissed'] ?? false),
            'sections' => $sections,
            'missing' => $missing,
            'modules' => $this->moduleReadiness($tenantId, $sections),
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'org_type' => $orgType,
            ] : null,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionBasic(?Organization $organization): array
    {
        $missing = [];
        if (! $organization || trim((string) $organization->name) === '') {
            $missing[] = ['key' => 'org_name', 'label' => 'Organization name', 'href' => '/clinic/organization'];
        }
        $percent = $missing === [] ? 100 : 0;

        return [
            'key' => 'basic',
            'label' => 'Basic organization',
            'weight' => 25,
            'complete' => $missing === [],
            'percent' => $percent,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionContact(?Organization $organization, ?Clinic $clinic): array
    {
        $missing = [];
        $phone = $organization?->phone ?: $clinic?->phone;
        $city = $organization?->city ?: $clinic?->city;
        if (! $phone) {
            $missing[] = ['key' => 'phone', 'label' => 'Phone', 'href' => '/clinic/organization'];
        }
        if (! $city) {
            $missing[] = ['key' => 'city', 'label' => 'City', 'href' => '/clinic/organization'];
        }
        $checks = 2;
        $done = $checks - count($missing);

        return [
            'key' => 'contact',
            'label' => 'Contact / location',
            'weight' => 15,
            'complete' => $missing === [],
            'percent' => (int) round(($done / $checks) * 100),
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionPublic(?Organization $organization, ?Clinic $clinic): array
    {
        $missing = [];
        $description = $organization?->description ?: $clinic?->description;
        $about = data_get($organization?->meta, 'profile.about')
            ?: data_get($clinic?->meta, 'profile.about');
        if (! $description && ! $about) {
            $missing[] = ['key' => 'description', 'label' => 'Description / about', 'href' => '/clinic/website'];
        }
        $tagline = data_get($organization?->meta, 'profile.tagline')
            ?: data_get($clinic?->meta, 'profile.tagline');
        if (! $tagline) {
            $missing[] = ['key' => 'tagline', 'label' => 'Tagline', 'href' => '/clinic/website'];
        }

        $checks = 2;
        $done = $checks - count($missing);

        return [
            'key' => 'public',
            'label' => 'Public profile',
            'weight' => 20,
            'complete' => $missing === [],
            'percent' => (int) round(($done / max(1, $checks)) * 100),
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionHours(int $tenantId, ?Clinic $clinic): array
    {
        $hasWorkingHours = WorkingHour::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->exists();

        $hasSchedules = Schedule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        $missing = [];
        if (! $hasWorkingHours && ! $hasSchedules) {
            $missing[] = ['key' => 'working_hours', 'label' => 'Working hours', 'href' => '/clinic/settings/hours'];
        }

        return [
            'key' => 'hours',
            'label' => 'Working hours',
            'weight' => 15,
            'complete' => $missing === [],
            'percent' => $missing === [] ? 100 : 0,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionServices(int $tenantId, ?Clinic $clinic): array
    {
        $count = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->count();

        $missing = [];
        if ($count < 1) {
            $missing[] = ['key' => 'services', 'label' => 'Add services', 'href' => '/clinic/settings/services'];
        }

        return [
            'key' => 'services',
            'label' => 'Services',
            'weight' => 10,
            'complete' => $missing === [],
            'percent' => $missing === [] ? 100 : 0,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionBranding(?Organization $organization, ?Clinic $clinic): array
    {
        $logo = data_get($organization?->meta, 'branding.logo_path')
            ?: $clinic?->logo_path
            ?: data_get($clinic?->meta, 'branding.logo_path');
        $missing = [];
        if (! $logo) {
            $missing[] = ['key' => 'logo', 'label' => 'Upload logo', 'href' => '/clinic/website'];
        }

        return [
            'key' => 'branding',
            'label' => 'Logo / branding',
            'weight' => 5,
            'complete' => $missing === [],
            'percent' => $missing === [] ? 100 : 0,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionStaff(int $tenantId): array
    {
        $count = Provider::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();
        $missing = [];
        if ($count < 1) {
            $missing[] = ['key' => 'staff', 'label' => 'Add staff / doctors', 'href' => '/clinic/staff'];
        }

        return [
            'key' => 'staff',
            'label' => 'Staff',
            'weight' => 5,
            'complete' => $missing === [],
            'percent' => $missing === [] ? 100 : 0,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: int, complete: bool, percent: int, missing: list<array{key: string, label: string, href: string}>}
     */
    private function sectionCommunication(?Organization $organization, ?Clinic $clinic): array
    {
        $whatsapp = $clinic?->whatsapp_number ?: data_get($organization?->meta, 'whatsapp');
        $email = $organization?->email ?: $clinic?->email;
        $missing = [];
        if (! $whatsapp && ! $email) {
            $missing[] = ['key' => 'communication', 'label' => 'WhatsApp or email', 'href' => '/clinic/website'];
        }

        return [
            'key' => 'communication',
            'label' => 'Communication',
            'weight' => 5,
            'complete' => $missing === [],
            'percent' => $missing === [] ? 100 : 0,
            'missing' => $missing,
        ];
    }

    /**
     * @param  list<array{key: string, complete: bool}>  $sections
     * @return array<string, array{enabled: bool, ready: bool, missing: list<string>}>
     */
    private function moduleReadiness(int $tenantId, array $sections): array
    {
        $activeKeys = TenantModule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('module')
            ->get()
            ->map(fn ($row) => $row->module?->key)
            ->filter()
            ->values()
            ->all();

        $hoursDone = collect($sections)->firstWhere('key', 'hours')['complete'] ?? false;
        $servicesDone = collect($sections)->firstWhere('key', 'services')['complete'] ?? false;
        $contactDone = collect($sections)->firstWhere('key', 'contact')['complete'] ?? false;
        $basicDone = collect($sections)->firstWhere('key', 'basic')['complete'] ?? false;
        $publicDone = collect($sections)->firstWhere('key', 'public')['complete'] ?? false;

        $defs = [
            'appointments' => [
                'requirements' => array_values(array_filter([
                    ! $basicDone ? 'organization_name' : null,
                    ! $contactDone ? 'contact' : null,
                    ! $hoursDone ? 'working_hours' : null,
                ])),
            ],
            'billing' => [
                'requirements' => array_values(array_filter([
                    ! $servicesDone ? 'services' : null,
                ])),
            ],
            'prescriptions' => [
                'requirements' => array_values(array_filter([
                    ! $basicDone ? 'provider_details' : null,
                ])),
            ],
            'patients' => ['requirements' => []],
            'clinical' => ['requirements' => []],
            'health_guide' => ['requirements' => []],
            'whatsapp' => [
                'requirements' => array_values(array_filter([
                    ! $contactDone ? 'communication' : null,
                ])),
            ],
            'media' => ['requirements' => []],
            'notice_board' => ['requirements' => []],
            'reviews' => [
                'requirements' => array_values(array_filter([
                    ! $publicDone ? 'public_profile' : null,
                ])),
            ],
        ];

        $out = [];
        foreach ($defs as $key => $def) {
            $enabled = in_array($key, $activeKeys, true);
            $missing = $def['requirements'];
            $out[$key] = [
                'enabled' => $enabled,
                'ready' => $enabled && $missing === [],
                'missing' => $missing,
            ];
        }

        return $out;
    }
}
