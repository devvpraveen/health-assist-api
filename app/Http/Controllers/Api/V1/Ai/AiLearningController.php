<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiAuditLog;
use App\Models\AiKnowledgeDocument;
use App\Models\AiLearningCandidate;
use App\Models\AiLearningSignal;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingJob;
use App\Models\User;
use App\Services\AI\Learning\DatasetBuilder;
use App\Services\AI\Learning\EvaluationService;
use App\Services\AI\Learning\KnowledgeRetriever;
use App\Services\AI\Learning\LearningEngine;
use App\Services\AI\Learning\TrainingJobService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiLearningController extends Controller
{
    public function storeFeedback(Request $request, LearningEngine $engine): JsonResponse
    {
        $this->ensureCanView($request->user());

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        abort_if($tenantId === null, 403, 'Tenant context required.');

        $data = $request->validate([
            'agent' => ['required', 'string', 'max:100'],
            'source' => ['required', 'string', Rule::in(['patient', 'clinic', 'doctor', 'system'])],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'helpful' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'original_output' => ['nullable', 'string'],
            'corrected_output' => ['nullable', 'string'],
            'usage_record_id' => ['nullable', 'integer'],
            'audit_log_id' => ['nullable', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
        ]);

        $feedback = $engine->submitFeedback([
            ...$data,
            'tenant_id' => (int) $tenantId,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $feedback], 201);
    }

    public function storeSignal(Request $request, LearningEngine $engine): JsonResponse
    {
        $this->ensureCanView($request->user());

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        abort_if($tenantId === null, 403, 'Tenant context required.');

        $data = $request->validate([
            'agent' => ['required', 'string', 'max:100'],
            'signal_type' => ['required', 'string', Rule::in(AiLearningSignal::TYPES)],
            'source' => ['required', 'string', Rule::in(['patient', 'clinic', 'doctor', 'system'])],
            'patient_id' => ['nullable', 'integer'],
            'usage_record_id' => ['nullable', 'integer'],
            'audit_log_id' => ['nullable', 'integer'],
            'payload' => ['nullable', 'array'],
        ]);

        $signal = $engine->recordSignal(
            tenantId: (int) $tenantId,
            agent: $data['agent'],
            signalType: $data['signal_type'],
            source: $data['source'],
            actorUserId: $request->user()?->id,
            patientId: $data['patient_id'] ?? null,
            usageRecordId: $data['usage_record_id'] ?? null,
            auditLogId: $data['audit_log_id'] ?? null,
            payload: $data['payload'] ?? [],
        );

        return response()->json(['data' => $signal], 201);
    }

    public function summary(Request $request, LearningEngine $engine): JsonResponse
    {
        $this->ensureCanView($request->user());
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;

        return response()->json([
            'data' => $engine->summary($tenantId ? (int) $tenantId : null),
            'disclaimer' => config('ai.disclaimer'),
        ]);
    }

    public function reviewAuditLog(Request $request, AiAuditLog $auditLog): JsonResponse
    {
        $this->ensureCanManage($request->user());

        $data = $request->validate([
            'review_status' => ['required', 'string', Rule::in([
                AiAuditLog::REVIEW_APPROVED,
                AiAuditLog::REVIEW_REJECTED,
                AiAuditLog::REVIEW_PENDING,
            ])],
        ]);

        $auditLog->update([
            'review_status' => $data['review_status'],
            'reviewer_user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $auditLog->fresh()]);
    }

    public function candidates(Request $request): JsonResponse
    {
        $this->ensureCanManage($request->user());
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;

        $rows = AiLearningCandidate::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($rows);
    }

    public function reviewCandidate(Request $request, AiLearningCandidate $candidate, LearningEngine $engine): JsonResponse
    {
        $this->ensureCanManage($request->user());

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in([
                AiLearningCandidate::STATUS_APPROVED,
                AiLearningCandidate::STATUS_REJECTED,
                AiLearningCandidate::STATUS_QUEUED,
            ])],
            'deidentified' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('deidentified', $data)) {
            $candidate->deidentified = (bool) $data['deidentified'];
            $candidate->save();
        }

        $updated = $engine->reviewCandidate($candidate, $data['status'], (int) $request->user()->id);

        return response()->json(['data' => $updated]);
    }

    public function buildDataset(Request $request, DatasetBuilder $builder): JsonResponse
    {
        $this->ensureCanManage($request->user());

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'agent' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;

        $dataset = $builder->buildFromApproved(
            key: $data['key'],
            name: $data['name'],
            agent: $data['agent'] ?? null,
            tenantId: $tenantId ? (int) $tenantId : null,
            limit: (int) ($data['limit'] ?? 500),
        );

        return response()->json(['data' => $dataset->loadCount('items')], 201);
    }

    public function queueTrainingJob(Request $request, TrainingJobService $training): JsonResponse
    {
        $this->ensureCanTrain($request->user());

        $data = $request->validate([
            'dataset_id' => ['required', 'integer', 'exists:ai_training_datasets,id'],
            'driver' => ['nullable', 'string', Rule::in(['mock', 'python'])],
            'base_model_key' => ['nullable', 'string', 'max:100'],
            'require_deidentified' => ['nullable', 'boolean'],
        ]);

        $dataset = AiTrainingDataset::query()->with('items')->findOrFail($data['dataset_id']);
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        if ($tenantId !== null && $dataset->tenant_id !== null && (int) $dataset->tenant_id !== (int) $tenantId) {
            abort(404);
        }

        $job = $training->queue(
            dataset: $dataset,
            actor: $request->user(),
            driver: $data['driver'] ?? null,
            baseModelKey: $data['base_model_key'] ?? null,
            requireDeidentified: array_key_exists('require_deidentified', $data)
                ? (bool) $data['require_deidentified']
                : (bool) config('ai.training.require_deidentified', true),
        );

        return response()->json([
            'data' => $this->trainingJobPayload($job->fresh(['dataset', 'evalRun', 'modelVersion'])),
        ], 202);
    }

    public function trainingJobs(Request $request): JsonResponse
    {
        $this->ensureCanTrain($request->user());
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;

        $rows = AiTrainingJob::query()
            ->with(['dataset', 'modelVersion'])
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('id')
            ->paginate(20);

        $rows->getCollection()->transform(fn (AiTrainingJob $job) => $this->trainingJobPayload($job));

        return response()->json($rows);
    }

    public function showTrainingJob(Request $request, AiTrainingJob $trainingJob): JsonResponse
    {
        $this->ensureCanTrain($request->user());
        $trainingJob->load(['dataset', 'evalRun', 'modelVersion']);

        return response()->json(['data' => $this->trainingJobPayload($trainingJob)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function trainingJobPayload(AiTrainingJob $job): array
    {
        return [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'dataset_id' => $job->dataset_id,
            'status' => $job->status,
            'driver' => $job->driver,
            'eval_run_id' => $job->eval_run_id,
            'model_version_id' => $job->model_version_id,
            'lifecycle_status' => $job->modelVersion?->lifecycle_status,
            'weights_updated_in_production' => false,
            'metrics' => $job->metrics,
            'error_message' => $job->error_message,
            'started_at' => $job->started_at?->toIso8601String(),
            'finished_at' => $job->finished_at?->toIso8601String(),
        ];
    }

    public function evaluation(Request $request, EvaluationService $evaluation): JsonResponse
    {
        $this->ensureCanView($request->user());
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        $tenantId = $tenantId ? (int) $tenantId : null;

        $latest = $evaluation->latest($tenantId);
        if ($latest === null || $request->boolean('refresh')) {
            $latest = $evaluation->runSignalAggregation($tenantId);
        }

        return response()->json([
            'data' => [
                'overall_score' => $latest->overall_score,
                'groundedness' => $latest->groundedness,
                'safety' => $latest->safety,
                'helpfulness' => $latest->helpfulness,
                'note' => $latest->summary['note'] ?? 'Aggregated from learning signals. Offline benchmark suite is separate.',
                'summary' => $latest->summary,
                'run_id' => $latest->id,
            ],
            'disclaimer' => config('ai.disclaimer'),
        ]);
    }

    public function knowledgeRetrieve(Request $request, KnowledgeRetriever $retriever): JsonResponse
    {
        $this->ensureCanView($request->user());
        $data = $request->validate([
            'q' => ['required', 'string', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;

        return response()->json([
            'data' => $retriever->retrieve(
                $tenantId ? (int) $tenantId : null,
                $data['q'],
                (int) ($data['limit'] ?? 5),
            ),
        ]);
    }

    public function storeKnowledge(Request $request): JsonResponse
    {
        $this->ensureCanManage($request->user());

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', Rule::in(['guideline', 'clinic_doc', 'faq', 'seo'])],
            'body' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'approved'])],
            'meta' => ['nullable', 'array'],
        ]);

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        $status = $data['status'] ?? AiKnowledgeDocument::STATUS_DRAFT;

        $doc = AiKnowledgeDocument::query()->create([
            'tenant_id' => $tenantId,
            'title' => $data['title'],
            'source_type' => $data['source_type'],
            'status' => $status,
            'body' => $data['body'],
            'meta' => $data['meta'] ?? null,
            'approved_by' => $status === AiKnowledgeDocument::STATUS_APPROVED ? $request->user()?->id : null,
            'approved_at' => $status === AiKnowledgeDocument::STATUS_APPROVED ? now() : null,
        ]);

        return response()->json(['data' => $doc], 201);
    }

    private function ensureCanView(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('ai.view')),
            403,
        );
    }

    private function ensureCanManage(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('ai.manage')),
            403,
        );
    }

    private function ensureCanTrain(?User $user): void
    {
        abort_unless(
            $user !== null && (
                $user->isSuperAdmin()
                || $user->hasPermission('ai.manage')
                || $user->hasPermission('tenant.ai.manage')
            ),
            403,
        );
    }
}
