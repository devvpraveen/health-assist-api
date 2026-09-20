<?php

namespace App\Jobs;

use App\Models\ReportAnalysis;
use App\Services\ReportAi\ReportAnalysisService;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessReportAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reportAnalysisId) {}

    public function handle(ReportAnalysisService $service): void
    {
        $analysis = ReportAnalysis::query()->withoutGlobalScopes()->find($this->reportAnalysisId);

        if ($analysis === null) {
            return;
        }

        TenantContext::set($analysis->tenant_id);

        try {
            $service->process($analysis);
        } catch (Throwable) {
            // Failure status already persisted in ReportAnalysisService.
        } finally {
            TenantContext::clear();
        }
    }
}
