<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicOrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicOrganizationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $orgs = Organization::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->with(['clinics' => fn ($q) => $q->withoutGlobalScopes()
                ->where('is_public', true)
                ->where('status', 'active')])
            ->latest()
            ->paginate();

        return PublicOrganizationResource::collection($orgs);
    }

    public function show(string $uuidOrSlug): PublicOrganizationResource
    {
        $organization = Organization::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->where(function ($builder) use ($uuidOrSlug): void {
                $builder->where('uuid', $uuidOrSlug)
                    ->orWhere('slug', $uuidOrSlug);
            })
            ->with([
                'clinics' => fn ($q) => $q->withoutGlobalScopes()
                    ->where('is_public', true)
                    ->where('status', 'active')
                    ->with(['specialties', 'providers' => fn ($p) => $p->where('is_public', true)->where('status', 'active')]),
            ])
            ->firstOrFail();

        return new PublicOrganizationResource($organization);
    }
}
