<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LanguageController extends Controller
{
    public function index(Request $request, LanguageCatalog $catalog): AnonymousResourceCollection|JsonResponse
    {
        $scope = $request->string('scope')->toString() ?: null;
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;

        if ($scope !== null && $scope !== '' && ! in_array($scope, LanguageCatalog::SCOPES, true)) {
            return response()->json([
                'message' => 'Invalid language scope.',
                'errors' => ['scope' => ['The selected scope is invalid.']],
            ], 422);
        }

        $codes = $catalog->enabledCodes($scope ?: null, $tenantId);

        $languages = Language::query()
            ->whereIn('code', $codes)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return LanguageResource::collection($languages)->additional([
            'meta' => [
                'scope' => $scope ?: null,
                'codes' => $codes,
            ],
        ]);
    }
}
