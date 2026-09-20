<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WellnessContentResource;
use App\Models\WellnessContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicWellnessContentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $locale = $request->string('locale')->toString() ?: 'en';

        $query = WellnessContent::query()
            ->whereNull('tenant_id')
            ->published()
            ->wellnessOnly()
            ->where('locale', $locale)
            ->with('category')
            ->latest('published_at');

        if ($slug = $request->string('slug')->toString()) {
            $query->where('slug', $slug);
        }

        return WellnessContentResource::collection($query->paginate(50));
    }

    public function show(Request $request, string $slug): WellnessContentResource
    {
        $locale = $request->string('locale')->toString() ?: 'en';

        $content = WellnessContent::query()
            ->whereNull('tenant_id')
            ->published()
            ->wellnessOnly()
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->with('category')
            ->firstOrFail();

        return new WellnessContentResource($content);
    }
}
