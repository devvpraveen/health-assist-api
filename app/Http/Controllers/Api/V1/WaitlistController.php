<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Appointments\CancelWaitlistEntryAction;
use App\Actions\Appointments\CreateWaitlistEntryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWaitlistEntryRequest;
use App\Http\Resources\AppointmentWaitlistEntryResource;
use App\Models\AppointmentWaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WaitlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AppointmentWaitlistEntry::class);

        $query = AppointmentWaitlistEntry::query()
            ->with(['patient', 'provider', 'clinic', 'service'])
            ->latest();

        if ($clinicId = $request->integer('clinic_id')) {
            $query->where('clinic_id', $clinicId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return AppointmentWaitlistEntryResource::collection($query->paginate());
    }

    public function store(StoreWaitlistEntryRequest $request, CreateWaitlistEntryAction $action): JsonResponse
    {
        $entry = $action->handle($request->validated());

        return (new AppointmentWaitlistEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(
        AppointmentWaitlistEntry $entry,
        CancelWaitlistEntryAction $action,
    ): AppointmentWaitlistEntryResource {
        $this->authorize('cancel', $entry);

        return new AppointmentWaitlistEntryResource($action->handle($entry));
    }
}
