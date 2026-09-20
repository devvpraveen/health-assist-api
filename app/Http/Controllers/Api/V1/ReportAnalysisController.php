<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ReportAi\ApproveReportAnalysisAction;
use App\Actions\ReportAi\RegeneratePatientExplanationAction;
use App\Actions\ReportAi\RejectReportAnalysisAction;
use App\Actions\ReportAi\StartReportAnalysisAction;
use App\Exceptions\Modules\EntitlementLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportAi\ApproveReportAnalysisRequest;
use App\Http\Requests\Api\V1\ReportAi\RejectReportAnalysisRequest;
use App\Http\Resources\ReportAnalysisResource;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\ReportAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportAnalysisController extends Controller
{
    public function indexTenant(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ReportAnalysis::class);

        $query = ReportAnalysis::query()
            ->with(['document'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->string('report_type')->toString());
        }

        return ReportAnalysisResource::collection($query->paginate());
    }

    public function indexForPatient(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAnyForPatient', [ReportAnalysis::class, $patient]);

        return ReportAnalysisResource::collection(
            $patient->reportAnalyses()->with(['document', 'versions'])->latest()->paginate()
        );
    }

    public function show(Patient $patient, ReportAnalysis $analysis): ReportAnalysisResource
    {
        abort_unless($analysis->patient_id === $patient->id, 404);
        $this->authorize('view', $analysis);

        $analysis->load(['document', 'versions']);

        return new ReportAnalysisResource($analysis);
    }

    public function analyze(
        Request $request,
        Patient $patient,
        PatientDocument $document,
        StartReportAnalysisAction $action,
    ): JsonResponse {
        abort_unless($document->patient_id === $patient->id, 404);
        $this->authorize('analyze', [ReportAnalysis::class, $patient]);

        $validated = $request->validate([
            'report_type' => ['nullable', 'string', 'in:lab,imaging,prescription,other'],
        ]);

        try {
            $analysis = $action->handle($patient, $document, $validated, $request->user());
        } catch (EntitlementLimitExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'entitlement' => $e->snapshot,
            ], 429);
        }

        $analysis->load(['document', 'versions']);

        return (new ReportAnalysisResource($analysis))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(
        ApproveReportAnalysisRequest $request,
        Patient $patient,
        ReportAnalysis $analysis,
        ApproveReportAnalysisAction $action,
    ): ReportAnalysisResource {
        abort_unless($analysis->patient_id === $patient->id, 404);

        $updated = $action->handle($analysis, $request->validated(), $request->user());

        return new ReportAnalysisResource($updated->load(['document', 'versions']));
    }

    public function reject(
        RejectReportAnalysisRequest $request,
        Patient $patient,
        ReportAnalysis $analysis,
        RejectReportAnalysisAction $action,
    ): ReportAnalysisResource {
        abort_unless($analysis->patient_id === $patient->id, 404);

        $updated = $action->handle($analysis, $request->validated(), $request->user());

        return new ReportAnalysisResource($updated->load(['document', 'versions']));
    }

    public function regenerateExplanation(
        Request $request,
        Patient $patient,
        ReportAnalysis $analysis,
        RegeneratePatientExplanationAction $action,
    ): ReportAnalysisResource {
        abort_unless($analysis->patient_id === $patient->id, 404);
        $this->authorize('analyze', [ReportAnalysis::class, $patient]);

        $updated = $action->handle($analysis, $request->user());

        return new ReportAnalysisResource($updated->load(['document', 'versions']));
    }
}
