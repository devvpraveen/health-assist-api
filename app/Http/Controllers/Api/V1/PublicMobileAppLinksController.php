<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PublicMobileAppLinksController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'scheme' => config('mobile.scheme'),
            'hosts' => config('mobile.hosts'),
            'paths' => config('mobile.paths'),
        ]);
    }
}
