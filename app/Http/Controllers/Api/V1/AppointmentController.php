<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Appointments\BookAppointmentAction;
use App\Actions\Appointments\CancelAppointmentAction;
use App\Actions\Appointments\CheckInAppointmentAction;
use App\Actions\Appointments\RescheduleAppointmentAction;
use App\Actions\Appointments\UpdateAppointmentStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelAppointmentRequest;
use App\Http\Requests\Api\V1\RescheduleAppointmentRequest;
use App\Http\Requests\Api\V1\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        $query = Appointment::query()
            ->with(['patient', 'provider', 'clinic', 'service'])
            ->latest('starts_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($providerId = $request->integer('provider_id')) {
            $query->where('provider_id', $providerId);
        }

        if ($clinicId = $request->integer('clinic_id')) {
            $query->where('clinic_id', $clinicId);
        }

        if ($patientId = $request->integer('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->where('starts_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->string('date_to')->toString()) {
            $query->where('starts_at', '<=', $dateTo);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                        $patientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return AppointmentResource::collection($query->paginate());
    }

    public function store(StoreAppointmentRequest $request, BookAppointmentAction $action): JsonResponse
    {
        $appointment = $action->handle($request->validated());

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);

        return new AppointmentResource(
            $appointment->load(['patient', 'provider', 'clinic', 'service', 'reminders', 'rescheduledFrom'])
        );
    }

    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        CancelAppointmentAction $action,
    ): AppointmentResource {
        return new AppointmentResource($action->handle($appointment, $request->validated()));
    }

    public function reschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        RescheduleAppointmentAction $action,
    ): JsonResponse {
        $new = $action->handle($appointment, $request->validated());

        return (new AppointmentResource($new))
            ->response()
            ->setStatusCode(201);
    }

    public function checkIn(Appointment $appointment, CheckInAppointmentAction $action): AppointmentResource
    {
        $this->authorize('checkIn', $appointment);

        return new AppointmentResource($action->handle($appointment));
    }

    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
        UpdateAppointmentStatusAction $action,
    ): AppointmentResource {
        return new AppointmentResource($action->handle($appointment, $request->validated()));
    }
}
