<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointments\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, AvailabilityService $availabilityService): JsonResponse
    {
        $this->authorize('viewAvailability', Appointment::class);

        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'date' => ['required', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:'.AvailabilityService::MAX_DAYS],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
        ]);

        $slots = $availabilityService->slots(
            providerId: (int) $validated['provider_id'],
            date: $validated['date'],
            days: (int) ($validated['days'] ?? 1),
            serviceId: isset($validated['service_id']) ? (int) $validated['service_id'] : null,
            branchId: isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            clinicId: isset($validated['clinic_id']) ? (int) $validated['clinic_id'] : null,
        );

        return response()->json(['data' => $slots]);
    }
}
