<?php

namespace App\Actions\ReportAi;

use App\Models\ReportAnalysis;
use App\Models\User;
use App\Services\ReportAi\ReportAnalysisService;
use Illuminate\Validation\ValidationException;

class RegeneratePatientExplanationAction
{
    public function __construct(
        private ReportAnalysisService $reportAnalysisService,
    ) {}

    public function handle(ReportAnalysis $analysis, ?User $actor = null): ReportAnalysis
    {
        if ($analysis->status !== ReportAnalysis::STATUS_AWAITING_REVIEW) {
            throw ValidationException::withMessages([
                'status' => ['Explanation can only be regenerated while awaiting review.'],
            ]);
        }

        return $this->reportAnalysisService->regeneratePatientExplanation($analysis);
    }
}
