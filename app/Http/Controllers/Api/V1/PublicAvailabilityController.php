<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Services\Appointments\AvailabilityService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAvailabilityController extends Controller
{
    public function index(Request $request, AvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'provider_uuid' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:'.AvailabilityService::MAX_DAYS],
            'service_id' => ['nullable', 'integer'],
        ]);

        $provider = Provider::query()
            ->withoutGlobalScopes()
            ->where('uuid', $validated['provider_uuid'])
            ->where('is_public', true)
            ->where('status', 'active')
            ->firstOrFail();

        TenantContext::set($provider->tenant_id);

        try {
            $slots = $availabilityService->slots(
                providerId: $provider->id,
                date: $validated['date'],
                days: (int) ($validated['days'] ?? 1),
                serviceId: isset($validated['service_id']) ? (int) $validated['service_id'] : null,
            );
        } finally {
            TenantContext::clear();
        }

        return response()->json([
            'data' => [
                'provider_uuid' => $provider->uuid,
                'slots' => $slots,
            ],
        ]);
    }
}
