<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecialtyResource;
use App\Models\Specialty;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicSpecialtyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $specialties = Specialty::query()
            ->whereNull('tenant_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return SpecialtyResource::collection($specialties);
    }
}
