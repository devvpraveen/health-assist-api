<?php

namespace App\Services\I18n;

use App\Models\Language;
use App\Models\TenantSetting;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class LanguageCatalog
{
    public const SCOPES = [
        'patient_app',
        'clinic_app',
        'public_content',
        'notifications',
        'clinical_content',
    ];

    /**
     * @return list<string>
     */
    public function enabledCodes(?string $scope = null, ?int $tenantId = null): array
    {
        $query = Language::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($scope !== null) {
            $query->whereHas(
                'scopeAssignments',
                fn ($builder) => $builder
                    ->where('scope', $scope)
                    ->where('is_enabled', true),
            );
        }

        /** @var list<string> $codes */
        $codes = $query->pluck('code')->all();

        if ($tenantId === null) {
            return $codes;
        }

        $languages = $this->tenantLanguageSettings($tenantId);

        if ($languages === null) {
            return $codes;
        }

        $enabled = Arr::get($languages, 'enabled');
        if (is_array($enabled)) {
            $codes = array_values(array_intersect($codes, array_map('strval', $enabled)));
        }

        if ($scope !== null) {
            $scopeCodes = Arr::get($languages, "scopes.{$scope}");
            if (is_array($scopeCodes)) {
                $codes = array_values(array_intersect($codes, array_map('strval', $scopeCodes)));
            }
        }

        return $codes;
    }

    public function assertAllowed(string $code, string $scope, ?int $tenantId = null, string $attribute = 'locale'): void
    {
        if (! in_array($code, $this->enabledCodes($scope, $tenantId), true)) {
            throw ValidationException::withMessages([
                $attribute => "Language [{$code}] is not enabled for scope [{$scope}].",
            ]);
        }
    }

    public function isAllowed(string $code, string $scope, ?int $tenantId = null): bool
    {
        return in_array($code, $this->enabledCodes($scope, $tenantId), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forAdmin(): array
    {
        return Language::query()
            ->with('scopeAssignments')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(function (Language $language): array {
                $scopes = [];
                foreach (self::SCOPES as $scope) {
                    $assignment = $language->scopeAssignments->firstWhere('scope', $scope);
                    $scopes[] = [
                        'scope' => $scope,
                        'is_enabled' => (bool) ($assignment?->is_enabled ?? false),
                    ];
                }

                return [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                    'is_rtl' => $language->is_rtl,
                    'is_enabled' => $language->is_enabled,
                    'sort_order' => $language->sort_order,
                    'scopes' => $scopes,
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function tenantLanguageSettings(int $tenantId): ?array
    {
        $settings = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->value('settings');

        if (! is_array($settings) || ! isset($settings['languages']) || ! is_array($settings['languages'])) {
            return null;
        }

        return $settings['languages'];
    }

    /**
     * @param  array{enabled?: list<string>, default?: string, scopes?: array<string, list<string>>}  $payload
     * @return array{enabled: list<string>, default: string, scopes: array<string, list<string>>}
     */
    public function assertValidTenantSettings(array $payload, int $tenantId): array
    {
        $enabled = array_values(array_unique(array_map('strval', $payload['enabled'] ?? [])));
        $default = (string) ($payload['default'] ?? ($enabled[0] ?? 'en'));
        $scopes = $payload['scopes'] ?? [];

        $platformEnabled = Language::query()
            ->where('is_enabled', true)
            ->pluck('code')
            ->all();

        foreach ($enabled as $code) {
            if (! in_array($code, $platformEnabled, true)) {
                throw ValidationException::withMessages([
                    'enabled' => "Language [{$code}] is not platform-enabled.",
                ]);
            }
        }

        if ($enabled !== [] && ! in_array($default, $enabled, true)) {
            throw ValidationException::withMessages([
                'default' => 'Default language must be included in enabled languages.',
            ]);
        }

        $normalizedScopes = [];
        foreach (self::SCOPES as $scope) {
            $scopeCodes = array_values(array_unique(array_map(
                'strval',
                is_array($scopes[$scope] ?? null) ? $scopes[$scope] : $enabled,
            )));

            $platformScopeCodes = $this->enabledCodes($scope, null);

            foreach ($scopeCodes as $code) {
                if (! in_array($code, $enabled, true)) {
                    throw ValidationException::withMessages([
                        "scopes.{$scope}" => "Language [{$code}] must be in the tenant enabled list.",
                    ]);
                }

                if (! in_array($code, $platformScopeCodes, true)) {
                    throw ValidationException::withMessages([
                        "scopes.{$scope}" => "Language [{$code}] is not platform-enabled for scope [{$scope}].",
                    ]);
                }
            }

            $normalizedScopes[$scope] = $scopeCodes;
        }

        return [
            'enabled' => $enabled,
            'default' => $default,
            'scopes' => $normalizedScopes,
        ];
    }
}
