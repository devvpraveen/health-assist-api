<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Marketing\ShareMetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicShareMetaController extends Controller
{
    public function show(Request $request, ShareMetaService $shareMeta): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:seo_entity,clinic'],
            'slug' => ['required', 'string', 'max:255'],
            'entity_type' => ['required_if:type,seo_entity', 'nullable', 'string', 'max:64'],
            'locale' => ['sometimes', 'string', 'max:16'],
        ]);

        $meta = $shareMeta->resolve(
            type: $validated['type'],
            slug: $validated['slug'],
            entityType: $validated['entity_type'] ?? null,
            locale: $validated['locale'] ?? 'en',
        );

        if ($meta === null) {
            abort(404);
        }

        return response()->json(['data' => $meta]);
    }
}
