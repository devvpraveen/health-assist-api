<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicClinicResource;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicClinicController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Clinic::query()
            ->withoutGlobalScopes()
            ->where('is_public', true)
            ->where('status', 'active')
            ->with(['specialties']);

        if ($city = $request->string('city')->toString()) {
            $query->where('city', 'like', "%{$city}%");
        }

        if ($q = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($q): void {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }

        if ($specialty = $request->string('specialty')->toString()) {
            $query->whereHas('specialties', function ($builder) use ($specialty): void {
                $builder->where('slug', $specialty)
                    ->orWhere('specialties.uuid', $specialty);
            });
        }

        return PublicClinicResource::collection($query->latest()->paginate());
    }

    public function show(string $uuidOrSlug): PublicClinicResource
    {
        $clinic = Clinic::query()
            ->withoutGlobalScopes()
            ->where('is_public', true)
            ->where('status', 'active')
            ->where(function ($builder) use ($uuidOrSlug): void {
                $builder->where('uuid', $uuidOrSlug)
                    ->orWhere('slug', $uuidOrSlug);
            })
            ->with([
                'specialties',
                'services' => fn ($q) => $q->where('is_public', true)->where('status', 'active'),
                'providers' => fn ($q) => $q->where('is_public', true)->where('status', 'active'),
                'schedules' => fn ($q) => $q->where('is_active', true),
            ])
            ->firstOrFail();

        return new PublicClinicResource($clinic);
    }
}
