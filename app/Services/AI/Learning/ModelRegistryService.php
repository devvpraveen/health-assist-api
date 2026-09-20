<?php

namespace App\Services\AI\Learning;

use App\Models\AiModelVersion;

/**
 * Controlled model lifecycle. Runtime never promotes candidates to production.
 */
class ModelRegistryService
{
    public const LIFECYCLES = [
        'base',
        'candidate',
        'evaluating',
        'approved',
        'canary',
        'production',
        'retired',
    ];

    public function setLifecycle(AiModelVersion $version, string $status, array $meta = []): AiModelVersion
    {
        if (! in_array($status, self::LIFECYCLES, true)) {
            throw new \InvalidArgumentException("Invalid lifecycle status [{$status}].");
        }

        // Hard gate: never auto-jump to production from candidate without explicit approved|canary.
        if ($status === 'production') {
            $current = $version->lifecycle_status ?? 'base';
            if (! in_array($current, ['approved', 'canary', 'production'], true)) {
                throw new \RuntimeException('Model version must be approved or canary before production.');
            }
        }

        $version->lifecycle_status = $status;
        $version->lifecycle_meta = array_merge(
            is_array($version->lifecycle_meta) ? $version->lifecycle_meta : [],
            $meta,
            ['updated_at' => now()->toIso8601String()],
        );
        $version->save();

        return $version->refresh();
    }
}
