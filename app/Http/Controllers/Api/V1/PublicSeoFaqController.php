<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicSeoFaqResource;
use App\Models\SeoFaq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicSeoFaqController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $locale = $request->string('locale')->toString() ?: 'en';

        $query = SeoFaq::query()
            ->published()
            ->where('locale', $locale)
            ->with('entity')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($entity = $request->string('entity')->toString()) {
            $query->whereHas('entity', function ($builder) use ($entity): void {
                $builder->published()
                    ->whereNotNull('last_reviewed_at')
                    ->where(function ($inner) use ($entity): void {
                        $inner->where('slug', $entity)
                            ->orWhere('uuid', $entity);
                    });
            });
        }

        return PublicSeoFaqResource::collection($query->paginate(100));
    }
}
