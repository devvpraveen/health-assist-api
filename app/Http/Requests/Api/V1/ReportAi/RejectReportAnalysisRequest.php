<?php

namespace App\Http\Requests\Api\V1\ReportAi;

use App\Models\ReportAnalysis;
use Illuminate\Foundation\Http\FormRequest;

class RejectReportAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ReportAnalysis $analysis */
        $analysis = $this->route('analysis');

        return $this->user()?->can('review', $analysis) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinician_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
