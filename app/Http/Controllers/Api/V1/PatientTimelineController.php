<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientTimelineEventResource;
use App\Models\Patient;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientTimelineController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewTimeline', $patient);

        return PatientTimelineEventResource::collection(
            $patient->timelineEvents()
                ->orderByDesc('occurred_at')
                ->paginate()
        );
    }
}
