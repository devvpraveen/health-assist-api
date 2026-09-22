<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UiTranslation;
use App\Services\I18n\LanguageCatalog;
use App\Services\I18n\TranslationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UiTranslationController extends Controller
{
    public function index(Request $request, TranslationCatalog $catalog, LanguageCatalog $languages): JsonResponse
    {
        $data = $request->validate([
            'locale' => ['nullable', 'string', 'max:16'],
            'group' => ['nullable', 'string', 'max:64'],
        ]);

        $locale = strtolower((string) ($data['locale'] ?? TranslationCatalog::DEFAULT_LOCALE));
        $group = (string) ($data['group'] ?? UiTranslation::GROUP_WEBSITE);

        $enabled = $languages->enabledCodes('public_content');
        if ($enabled !== [] && ! in_array($locale, $enabled, true)) {
            $locale = in_array(TranslationCatalog::DEFAULT_LOCALE, $enabled, true)
                ? TranslationCatalog::DEFAULT_LOCALE
                : ($enabled[0] ?? TranslationCatalog::DEFAULT_LOCALE);
        }

        return response()->json([
            'data' => [
                'locale' => $locale,
                'group' => $group,
                'strings' => $catalog->dictionary($locale, $group),
            ],
            'meta' => [
                'fallback_locale' => TranslationCatalog::DEFAULT_LOCALE,
                'enabled_locales' => $enabled,
            ],
        ]);
    }
}
