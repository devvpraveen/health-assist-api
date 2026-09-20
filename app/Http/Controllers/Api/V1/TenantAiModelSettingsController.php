<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ai\UpdateTenantAiModelSettingsRequest;
use App\Services\AI\TenantAiModelSettings;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAiModelSettingsController extends Controller
{
    public function show(Request $request, TenantAiModelSettings $settings): JsonResponse
    {
        abort_unless(
            $request->user()?->isSuperAdmin()
                || $request->user()?->hasPermission('ai.view')
                || $request->user()?->hasPermission('ai.manage')
                || $request->user()?->hasPermission('tenant.ai.manage'),
            403,
        );

        $tenantId = TenantContext::id();
        abort_unless($tenantId !== null, 422, 'Tenant context is required.');

        return response()->json([
            'data' => $settings->get($tenantId),
        ]);
    }

    public function update(UpdateTenantAiModelSettingsRequest $request, TenantAiModelSettings $settings): JsonResponse
    {
        $tenantId = TenantContext::id();
        abort_unless($tenantId !== null, 422, 'Tenant context is required.');

        return response()->json([
            'data' => $settings->put($tenantId, $request->validated()),
        ]);
    }
}
