<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreLanguageRequest;
use App\Http\Requests\Api\V1\Admin\UpdateLanguageRequest;
use App\Http\Requests\Api\V1\Admin\UpdateLanguageScopesRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\I18n\LanguageCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LanguageController extends Controller
{
    public function index(LanguageCatalog $catalog): JsonResponse
    {
        $this->authorize('viewAny', Language::class);

        return response()->json([
            'data' => $catalog->forAdmin(),
        ]);
    }

    public function store(StoreLanguageRequest $request): JsonResponse
    {
        $language = Language::query()->create($request->validated());

        return (new LanguageResource($language->load('scopeAssignments')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateLanguageRequest $request, Language $language): LanguageResource
    {
        $language->update($request->validated());

        return new LanguageResource($language->fresh()->load('scopeAssignments'));
    }

    public function updateScopes(UpdateLanguageScopesRequest $request, Language $language): LanguageResource
    {
        $scopes = $request->validated('scopes');

        DB::transaction(function () use ($language, $scopes): void {
            foreach ($scopes as $scopeData) {
                $language->scopeAssignments()->updateOrCreate(
                    ['scope' => $scopeData['scope']],
                    ['is_enabled' => (bool) $scopeData['is_enabled']],
                );
            }
        });

        return new LanguageResource($language->fresh()->load('scopeAssignments'));
    }
}
