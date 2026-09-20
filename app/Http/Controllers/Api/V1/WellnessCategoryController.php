<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WellnessCategoryResource;
use App\Models\WellnessCategory;
use App\Models\WellnessContent;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WellnessCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WellnessContent::class);

        return WellnessCategoryResource::collection(
            WellnessCategory::query()->orderBy('sort_order')->orderBy('name')->get()
        );
    }
}
