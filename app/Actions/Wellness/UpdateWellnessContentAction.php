<?php

namespace App\Actions\Wellness;

use App\Models\WellnessContent;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateWellnessContentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(WellnessContent $content, array $data): WellnessContent
    {
        return DB::transaction(function () use ($content, $data): WellnessContent {
            if (config('wellness.force_non_clinical', true)) {
                $data['is_clinical_advice'] = false;
            }

            if (($data['status'] ?? null) === WellnessContent::STATUS_PUBLISHED && $content->published_at === null) {
                $data['published_at'] = $data['published_at'] ?? now();
            }

            $content->update($data);

            $this->auditLogger->log('wellness.content.updated', $content, [
                'content_uuid' => $content->uuid,
                'status' => $content->status,
            ]);

            return $content->refresh()->load('category');
        });
    }
}
