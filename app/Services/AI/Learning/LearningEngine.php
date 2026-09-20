<?php

namespace App\Services\AI\Learning;

use App\Models\AiFeedback;
use App\Models\AiLearningCandidate;
use App\Models\AiLearningSignal;
use App\Models\AiUsageRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LearningEngine
{
    public function __construct(
        private readonly MemoryService $memory,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordSignal(
        int $tenantId,
        string $agent,
        string $signalType,
        string $source,
        ?int $actorUserId = null,
        ?int $patientId = null,
        ?int $usageRecordId = null,
        ?int $auditLogId = null,
        ?Model $subject = null,
        array $payload = [],
    ): AiLearningSignal {
        if (! in_array($signalType, AiLearningSignal::TYPES, true)) {
            throw new \InvalidArgumentException("Unknown learning signal [{$signalType}].");
        }

        $signal = AiLearningSignal::query()->create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'patient_id' => $patientId,
            'agent' => $agent,
            'signal_type' => $signalType,
            'source' => $source,
            'usage_record_id' => $usageRecordId,
            'audit_log_id' => $auditLogId,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'payload' => $payload ?: null,
        ]);

        if (in_array($signalType, ['appointment_booked', 'provider_selected', 'followup_completed'], true)) {
            $this->memory->recordWorkflowOutcome(
                $tenantId,
                $agent,
                $signalType,
                is_array($payload['tool_chain'] ?? null) ? $payload['tool_chain'] : [$signalType],
                true,
            );
        }

        if (in_array($signalType, ['appointment_cancelled', 'provider_changed', 'patient_worsened'], true)) {
            $this->memory->recordWorkflowOutcome(
                $tenantId,
                $agent,
                $signalType,
                is_array($payload['tool_chain'] ?? null) ? $payload['tool_chain'] : [$signalType],
                false,
            );
        }

        return $signal;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitFeedback(array $data): AiFeedback
    {
        $feedback = AiFeedback::query()->create([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'agent' => $data['agent'],
            'source' => $data['source'],
            'rating' => $data['rating'] ?? null,
            'helpful' => $data['helpful'] ?? null,
            'comment' => $data['comment'] ?? null,
            'original_output' => $data['original_output'] ?? null,
            'corrected_output' => $data['corrected_output'] ?? null,
            'usage_record_id' => $data['usage_record_id'] ?? null,
            'audit_log_id' => $data['audit_log_id'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        $signalType = match (true) {
            $feedback->hasCorrection() => 'clinician_corrected',
            $feedback->helpful === true => 'patient_helpful',
            $feedback->helpful === false => 'patient_not_helpful',
            default => null,
        };

        $signal = null;
        if ($signalType) {
            $signal = $this->recordSignal(
                tenantId: (int) $feedback->tenant_id,
                agent: $feedback->agent,
                signalType: $signalType,
                source: $feedback->source,
                actorUserId: $feedback->user_id,
                patientId: $feedback->patient_id,
                usageRecordId: $feedback->usage_record_id,
                auditLogId: $feedback->audit_log_id,
                payload: ['feedback_id' => $feedback->id],
            );
        }

        if ($feedback->hasCorrection()) {
            $this->promoteCorrectionCandidate($feedback, $signal?->id);
        }

        return $feedback;
    }

    public function promoteCorrectionCandidate(AiFeedback $feedback, ?int $signalId = null): AiLearningCandidate
    {
        $input = null;
        if ($feedback->usage_record_id) {
            $usage = AiUsageRecord::query()->find($feedback->usage_record_id);
            $input = $usage ? 'usage:'.$usage->request_id : null;
        }

        return AiLearningCandidate::query()->create([
            'tenant_id' => $feedback->tenant_id,
            'agent' => $feedback->agent,
            'status' => AiLearningCandidate::STATUS_PENDING_REVIEW,
            'input_redacted' => $input,
            'original_output' => $feedback->original_output,
            'corrected_output' => $feedback->corrected_output,
            'deidentified' => false,
            'feedback_id' => $feedback->id,
            'signal_id' => $signalId,
            'meta' => ['source' => $feedback->source],
        ]);
    }

    public function reviewCandidate(AiLearningCandidate $candidate, string $status, int $reviewerId): AiLearningCandidate
    {
        if (! in_array($status, [
            AiLearningCandidate::STATUS_APPROVED,
            AiLearningCandidate::STATUS_REJECTED,
            AiLearningCandidate::STATUS_QUEUED,
        ], true)) {
            throw new \InvalidArgumentException('Invalid candidate status.');
        }

        $candidate->fill([
            'status' => $status,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'deidentified' => $status === AiLearningCandidate::STATUS_APPROVED
                ? (bool) $candidate->deidentified
                : $candidate->deidentified,
        ]);
        $candidate->save();

        return $candidate->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?int $tenantId = null): array
    {
        $signalQuery = AiLearningSignal::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $byType = (clone $signalQuery)
            ->select('signal_type', DB::raw('count(*) as total'))
            ->groupBy('signal_type')
            ->pluck('total', 'signal_type')
            ->all();

        $byAgent = (clone $signalQuery)
            ->select('agent', DB::raw('count(*) as total'))
            ->groupBy('agent')
            ->pluck('total', 'agent')
            ->all();

        $helpful = (int) ($byType['patient_helpful'] ?? 0);
        $notHelpful = (int) ($byType['patient_not_helpful'] ?? 0);
        $approved = (int) ($byType['clinician_approved'] ?? 0);
        $rejected = (int) ($byType['clinician_rejected'] ?? 0);
        $corrected = (int) ($byType['clinician_corrected'] ?? 0);

        $helpDenom = max(1, $helpful + $notHelpful);
        $reviewDenom = max(1, $approved + $rejected);

        $candidatesPending = AiLearningCandidate::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('status', AiLearningCandidate::STATUS_PENDING_REVIEW)
            ->count();

        $candidatesApproved = AiLearningCandidate::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('status', AiLearningCandidate::STATUS_APPROVED)
            ->count();

        return [
            'signals_by_type' => $byType,
            'signals_by_agent' => $byAgent,
            'helpfulness_rate' => round($helpful / $helpDenom, 4),
            'clinician_approval_rate' => round($approved / $reviewDenom, 4),
            'correction_count' => $corrected,
            'candidates_pending_review' => $candidatesPending,
            'candidates_approved' => $candidatesApproved,
            'note' => 'Live agents never auto-update model weights. Approved candidates feed few-shot memory and offline datasets only.',
        ];
    }
}
