<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicSeoEntityResource;
use App\Http\Resources\PublicSeoEntitySummaryResource;
use App\Models\SeoEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicSeoEntityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SeoEntity::query()
            ->published()
            ->whereNotNull('last_reviewed_at');

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        $locale = $request->string('locale')->toString() ?: 'en';
        $query->where('locale', $locale);

        return PublicSeoEntitySummaryResource::collection(
            $query->orderBy('title')->paginate(50)
        );
    }

    public function show(Request $request, string $type, string $slug): PublicSeoEntityResource
    {
        $locale = $request->string('locale')->toString() ?: 'en';

        $entity = SeoEntity::query()
            ->published()
            ->whereNotNull('last_reviewed_at')
            ->where('type', $type)
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->with([
                'relatedEntities' => fn ($q) => $q->published()->whereNotNull('last_reviewed_at'),
                'faqs' => fn ($q) => $q->published()->orderBy('sort_order'),
            ])
            ->firstOrFail();

        return new PublicSeoEntityResource($entity);
    }
}
