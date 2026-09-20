<?php

namespace App\Http\Resources;

use App\Models\ReportAnalysisVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReportAnalysisVersion
 */
class ReportAnalysisVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_analysis_id' => $this->report_analysis_id,
            'version' => $this->version,
            'kind' => $this->kind,
            'payload' => $this->payload,
            'payload_text' => $this->payload_text,
            'created_by_user_id' => $this->created_by_user_id,
            'source' => $this->source,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
