<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProviderResource;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicProviderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Provider::query()
            ->withoutGlobalScopes()
            ->where('is_public', true)
            ->where('status', 'active')
            ->whereHas('clinic', function ($builder): void {
                $builder->withoutGlobalScopes()
                    ->where('is_public', true)
                    ->where('status', 'active');
            })
            ->with(['specialties', 'clinic']);

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($clinic = $request->string('clinic')->toString()) {
            $query->whereHas('clinic', function ($builder) use ($clinic): void {
                $builder->withoutGlobalScopes()
                    ->where(function ($inner) use ($clinic): void {
                        $inner->where('uuid', $clinic)
                            ->orWhere('slug', $clinic);
                    });
            });
        }

        if ($city = $request->string('city')->toString()) {
            $query->whereHas('clinic', function ($builder) use ($city): void {
                $builder->withoutGlobalScopes()
                    ->where('city', 'like', "%{$city}%");
            });
        }

        if ($specialty = $request->string('specialty')->toString()) {
            $query->whereHas('specialties', function ($builder) use ($specialty): void {
                $builder->where('slug', $specialty)
                    ->orWhere('specialties.uuid', $specialty);
            });
        }

        if ($language = $request->string('language')->toString()) {
            $query->whereJsonContains('languages', $language);
        }

        if ($q = $request->string('q')->toString()) {
            $like = '%'.$q.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('display_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('bio', 'like', $like)
                    ->orWhereHas('specialties', function ($specialty) use ($like): void {
                        $specialty->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
            });
        }

        return PublicProviderResource::collection($query->latest()->paginate());
    }

    public function show(string $uuid): PublicProviderResource
    {
        $provider = Provider::query()
            ->withoutGlobalScopes()
            ->where('is_public', true)
            ->where('status', 'active')
            ->where('uuid', $uuid)
            ->whereHas('clinic', function ($builder): void {
                $builder->withoutGlobalScopes()
                    ->where('is_public', true)
                    ->where('status', 'active');
            })
            ->with([
                'specialties',
                'clinic',
                'schedules' => fn ($q) => $q->where('is_active', true),
            ])
            ->firstOrFail();

        return new PublicProviderResource($provider);
    }
}
