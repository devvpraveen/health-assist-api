<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTenantLanguageSettingsRequest;
use App\Models\TenantSetting;
use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantLanguageSettingsController extends Controller
{
    public function show(Request $request, LanguageCatalog $catalog): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('languages.view')
                || $request->user()?->hasPermission('tenant.languages.manage')
                || $request->user()?->isSuperAdmin(),
            403,
        );

        $tenantId = TenantContext::id();
        abort_unless($tenantId !== null, 422, 'Tenant context is required.');

        $settings = $catalog->tenantLanguageSettings($tenantId);

        return response()->json([
            'data' => $settings ?? [
                'enabled' => $catalog->enabledCodes(null, null),
                'default' => $catalog->enabledCodes(null, null)[0] ?? 'en',
                'scopes' => collect(LanguageCatalog::SCOPES)
                    ->mapWithKeys(fn (string $scope) => [$scope => $catalog->enabledCodes($scope, null)])
                    ->all(),
                'inherited' => true,
            ],
            'meta' => [
                'platform' => [
                    'enabled' => $catalog->enabledCodes(null, null),
                    'scopes' => collect(LanguageCatalog::SCOPES)
                        ->mapWithKeys(fn (string $scope) => [$scope => $catalog->enabledCodes($scope, null)])
                        ->all(),
                ],
            ],
        ]);
    }

    public function update(UpdateTenantLanguageSettingsRequest $request, LanguageCatalog $catalog): JsonResponse
    {
        $tenantId = TenantContext::id();
        abort_unless($tenantId !== null, 422, 'Tenant context is required.');

        $languages = $catalog->assertValidTenantSettings($request->validated(), $tenantId);

        $setting = TenantSetting::query()->firstOrNew(['tenant_id' => $tenantId]);
        $settings = is_array($setting->settings) ? $setting->settings : [];
        $settings['languages'] = $languages;
        $setting->settings = $settings;
        $setting->tenant_id = $tenantId;
        $setting->save();

        return response()->json([
            'data' => $languages,
        ]);
    }
}
