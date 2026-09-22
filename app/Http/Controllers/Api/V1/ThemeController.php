<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Theme\PlatformThemeService;
use Illuminate\Http\JsonResponse;

class ThemeController extends Controller
{
    public function show(PlatformThemeService $themes): JsonResponse
    {
        $config = $themes->publishedConfig();

        return response()->json([
            'data' => $config,
        ]);
    }
}
