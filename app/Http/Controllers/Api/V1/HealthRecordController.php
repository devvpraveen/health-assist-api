<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\CreateHealthRecordAction;
use App\Actions\Patients\UpdateHealthRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreHealthRecordRequest;
use App\Http\Requests\Api\V1\UpdateHealthRecordRequest;
use App\Http\Resources\HealthRecordResource;
use App\Models\HealthRecord;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class HealthRecordController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [HealthRecord::class, $patient]);

        $query = $patient->healthRecords()->latest();

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        return HealthRecordResource::collection($query->paginate());
    }

    public function store(
        StoreHealthRecordRequest $request,
        Patient $patient,
        CreateHealthRecordAction $action,
    ): JsonResponse {
        $record = $action->handle($patient, $request->validated());

        return (new HealthRecordResource($record))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, HealthRecord $healthRecord): HealthRecordResource
    {
        $this->authorize('view', $healthRecord);

        return new HealthRecordResource($healthRecord);
    }

    public function update(
        UpdateHealthRecordRequest $request,
        Patient $patient,
        HealthRecord $healthRecord,
        UpdateHealthRecordAction $action,
    ): HealthRecordResource {
        return new HealthRecordResource(
            $action->handle($healthRecord, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        HealthRecord $healthRecord,
        AuditLogger $auditLogger,
        PatientTimelineRecorder $timelineRecorder,
    ): Response {
        $this->authorize('delete', $healthRecord);

        DB::transaction(function () use ($patient, $healthRecord, $auditLogger, $timelineRecorder): void {
            $timelineRecorder->record(
                $patient,
                'record.deleted',
                'Health record deleted',
                subject: $healthRecord,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'health_record_uuid' => $healthRecord->uuid,
                ],
            );

            $auditLogger->log('health_record.deleted', $healthRecord, [
                'patient_uuid' => $patient->uuid,
                'health_record_uuid' => $healthRecord->uuid,
            ]);

            $healthRecord->delete();
        });

        return response()->noContent();
    }
}
