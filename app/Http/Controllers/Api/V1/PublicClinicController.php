<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicClinicResource;
use App\Http\Resources\WorkingHourResource;
use App\Models\Clinic;
use App\Models\ClinicNotice;
use App\Models\OrganizationReview;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

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
                'services' => fn ($q) => $q->where('is_public', true)->where('status', 'active')->with('specialty'),
                'providers' => fn ($q) => $q->where('is_public', true)->where('status', 'active')->with('specialties'),
                'schedules' => fn ($q) => $q->where('is_active', true),
                'organization',
            ])
            ->firstOrFail();

        $hours = WorkingHour::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $clinic->tenant_id)
            ->where('clinic_id', $clinic->id)
            ->orderBy('day_of_week')
            ->orderBy('shift_index')
            ->get();

        $notices = ClinicNotice::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $clinic->tenant_id)
            ->where('status', 'published')
            ->where('is_public', true)
            ->latest('published_at')
            ->limit(10)
            ->get();

        $reviews = OrganizationReview::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $clinic->tenant_id)
            ->where('status', 'published')
            ->latest()
            ->limit(20)
            ->get();

        $sections = data_get($clinic->meta, 'public_sections')
            ?? data_get($clinic->organization?->meta, 'public_sections')
            ?? collect(['about', 'services', 'doctors', 'working_hours', 'appointments', 'facilities', 'videos', 'reviews', 'contact'])
                ->values()
                ->map(fn (string $key, int $i) => [
                    'key' => $key,
                    'label' => Str::headline($key),
                    'visible' => true,
                    'order' => $i + 1,
                ])
                ->all();

        $resource = new PublicClinicResource($clinic);
        $resource->additional([
            'meta' => [
                'working_hours' => WorkingHourResource::collection($hours),
                'notices' => $notices,
                'reviews' => $reviews,
                'sections' => $sections,
                'canonical_path' => '/provider/'.$clinic->slug,
            ],
        ]);

        return $resource;
    }
}
