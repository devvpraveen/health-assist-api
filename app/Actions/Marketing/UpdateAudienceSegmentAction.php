<?php

namespace App\Actions\Marketing;

use App\Models\AudienceSegment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateAudienceSegmentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AudienceSegment $segment, array $data): AudienceSegment
    {
        return DB::transaction(function () use ($segment, $data): AudienceSegment {
            if (isset($data['definition']) && is_array($data['definition'])) {
                $data['definition'] = collect($data['definition'])
                    ->only(['inactive_days', 'new_user', 'new_user_days', 'appointment_upcoming'])
                    ->all();
            }

            $segment->fill(collect($data)->only(['name', 'key', 'definition'])->all());
            $segment->save();

            $this->auditLogger->log('marketing.segment.updated', $segment, [
                'key' => $segment->key,
            ]);

            return $segment->refresh();
        });
    }
}
