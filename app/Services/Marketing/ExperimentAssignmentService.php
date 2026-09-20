<?php

namespace App\Services\Marketing;

use App\Models\Experiment;
use App\Models\ExperimentAssignment;
use App\Models\ExperimentVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExperimentAssignmentService
{
    /**
     * Deterministic assignment: hash(anonymous_id + experiment_key) % 100 against cumulative weights.
     */
    public function assign(Experiment $experiment, string $anonymousId, ?int $userId = null): ExperimentAssignment
    {
        if ($experiment->status !== Experiment::STATUS_RUNNING) {
            throw ValidationException::withMessages([
                'experiment' => 'Experiment is not running.',
            ]);
        }

        $existing = ExperimentAssignment::query()
            ->where('experiment_id', $experiment->id)
            ->where('anonymous_id', $anonymousId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $variants = $experiment->variants()->orderBy('id')->get();

        if ($variants->isEmpty()) {
            throw ValidationException::withMessages([
                'experiment' => 'Experiment has no variants.',
            ]);
        }

        $variantKey = $this->pickVariantKey($anonymousId, $experiment->key, $variants);

        return DB::transaction(function () use ($experiment, $anonymousId, $userId, $variantKey): ExperimentAssignment {
            return ExperimentAssignment::query()->firstOrCreate(
                [
                    'experiment_id' => $experiment->id,
                    'anonymous_id' => $anonymousId,
                ],
                [
                    'user_id' => $userId,
                    'variant_key' => $variantKey,
                    'assigned_at' => now(),
                ]
            );
        });
    }

    /**
     * @param  Collection<int, ExperimentVariant>  $variants
     */
    public function pickVariantKey(string $anonymousId, string $experimentKey, $variants): string
    {
        $bucket = $this->bucket($anonymousId, $experimentKey);
        $totalWeight = max(1, (int) $variants->sum('weight'));
        $cursor = 0;

        foreach ($variants as $variant) {
            $cursor += (int) $variant->weight;
            $threshold = (int) floor(($cursor / $totalWeight) * 100);
            if ($bucket < $threshold || $cursor >= $totalWeight) {
                return $variant->key;
            }
        }

        return $variants->last()->key;
    }

    public function bucket(string $anonymousId, string $experimentKey): int
    {
        $hash = hash('sha256', $anonymousId.'|'.$experimentKey);

        return hexdec(substr($hash, 0, 8)) % 100;
    }
}
