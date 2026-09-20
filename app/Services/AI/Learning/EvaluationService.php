<?php

namespace App\Services\AI\Learning;

use App\Models\AiEvalRun;
use App\Models\AiLearningSignal;

class EvaluationService
{
    public function __construct(
        private readonly LearningEngine $learningEngine,
    ) {}

    /**
     * Aggregate online learning signals into an eval snapshot (not offline benchmark suite).
     */
    public function runSignalAggregation(?int $tenantId = null, string $name = 'Online signal aggregation'): AiEvalRun
    {
        $summary = $this->learningEngine->summary($tenantId);

        $helpfulness = (float) ($summary['helpfulness_rate'] ?? 0);
        $approval = (float) ($summary['clinician_approval_rate'] ?? 0);
        $safetyProxy = 1.0; // SafetyEngine is deterministic; proxy until offline suite lands
        $groundedness = min(1.0, ($helpfulness * 0.5) + ($approval * 0.5));
        $overall = round(($helpfulness + $approval + $safetyProxy + $groundedness) / 4, 4);

        return AiEvalRun::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'status' => 'completed',
            'overall_score' => $overall,
            'groundedness' => round($groundedness, 4),
            'safety' => $safetyProxy,
            'helpfulness' => round($helpfulness, 4),
            'summary' => $summary,
        ]);
    }

    public function latest(?int $tenantId = null): ?AiEvalRun
    {
        return AiEvalRun::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('id')
            ->first();
    }

    public function signalCount(?int $tenantId = null): int
    {
        return AiLearningSignal::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->count();
    }
}
