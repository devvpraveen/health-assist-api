<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\UiTranslation;
use App\Services\I18n\TranslationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UiTranslationController extends Controller
{
    public function index(Request $request, TranslationCatalog $catalog): JsonResponse
    {
        $this->authorize('viewAny', Language::class);

        $data = $request->validate([
            'group' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:16'],
        ]);

        $group = $data['group'] ?? 'website';

        return response()->json([
            'data' => $catalog->forAdmin(
                $group,
                isset($data['locale']) ? strtolower($data['locale']) : null,
            ),
            'meta' => [
                'catalog_keys' => $group === 'website'
                    ? \App\Services\I18n\WebsiteUiCatalog::keys()
                    : [],
                'catalog_key_count' => $group === 'website'
                    ? count(\App\Services\I18n\WebsiteUiCatalog::keys())
                    : 0,
            ],
        ]);
    }

    public function upsert(Request $request, TranslationCatalog $catalog): JsonResponse
    {
        $this->authorize('create', Language::class);

        $data = $request->validate([
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.group' => ['sometimes', 'string', 'max:64'],
            'translations.*.key' => ['required', 'string', 'max:191'],
            'translations.*.locale' => ['required', 'string', 'max:16'],
            'translations.*.value' => ['nullable', 'string'],
        ]);

        $saved = $catalog->upsertMany($data['translations']);

        return response()->json(['data' => $saved]);
    }

    public function destroy(UiTranslation $translation, TranslationCatalog $catalog): JsonResponse
    {
        $this->authorize('create', Language::class);

        $group = $translation->group;
        $locale = $translation->locale;
        $translation->delete();
        $catalog->forget($locale, $group);

        return response()->json(['message' => 'Translation deleted']);
    }
}
