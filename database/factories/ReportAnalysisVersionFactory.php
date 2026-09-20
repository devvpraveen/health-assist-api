<?php

namespace Database\Factories;

use App\Models\ReportAnalysis;
use App\Models\ReportAnalysisVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportAnalysisVersion>
 */
class ReportAnalysisVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_analysis_id' => ReportAnalysis::factory(),
            'version' => 1,
            'kind' => ReportAnalysisVersion::KIND_OCR,
            'payload' => ['provider' => 'mock'],
            'payload_text' => 'sample ocr',
            'created_by_user_id' => null,
            'source' => ReportAnalysisVersion::SOURCE_SYSTEM,
        ];
    }
}
