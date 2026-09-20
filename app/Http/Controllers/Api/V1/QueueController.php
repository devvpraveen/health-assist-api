<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QueueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewQueue', Appointment::class);

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
            'date' => ['nullable', 'date'],
        ]);

        $day = CarbonImmutable::parse($validated['date'] ?? now()->toDateString());

        $query = Appointment::query()
            ->with(['patient', 'provider', 'clinic'])
            ->where('clinic_id', $validated['clinic_id'])
            ->whereBetween('starts_at', [$day->startOfDay(), $day->endOfDay()])
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_RESCHEDULED,
            ])
            ->orderByRaw('CASE WHEN checked_in_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('queue_number')
            ->orderBy('starts_at');

        return AppointmentResource::collection($query->get());
    }
}
