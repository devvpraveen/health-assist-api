<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalExercisePlan;
use App\Models\ClinicalExercisePlanItem;
use App\Models\Exercise;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncClinicalExercisePlanItemsAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array{items: list<array<string, mixed>>}  $data
     */
    public function handle(ClinicalExercisePlan $plan, array $data): ClinicalExercisePlan
    {
        return DB::transaction(function () use ($plan, $data): ClinicalExercisePlan {
            $plan->items()->delete();

            foreach ($data['items'] as $index => $item) {
                $exerciseId = $item['exercise_id'] ?? null;
                $customName = $item['custom_name'] ?? null;

                if ($exerciseId === null && blank($customName)) {
                    throw ValidationException::withMessages([
                        "items.{$index}" => ['Each item requires exercise_id or custom_name.'],
                    ]);
                }

                if ($exerciseId !== null) {
                    $exercise = Exercise::query()
                        ->visibleToTenant(TenantContext::id())
                        ->whereKey($exerciseId)
                        ->first();

                    if ($exercise === null) {
                        throw ValidationException::withMessages([
                            "items.{$index}.exercise_id" => ['Exercise not found for this tenant.'],
                        ]);
                    }
                }

                ClinicalExercisePlanItem::query()->create([
                    'exercise_plan_id' => $plan->id,
                    'exercise_id' => $exerciseId,
                    'custom_name' => $customName,
                    'frequency' => $item['frequency'] ?? null,
                    'sets' => $item['sets'] ?? null,
                    'reps' => $item['reps'] ?? null,
                    'duration_seconds' => $item['duration_seconds'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            }

            $this->auditLogger->log('clinical.exercise_plan.items_synced', $plan, [
                'exercise_plan_uuid' => $plan->uuid,
                'item_count' => count($data['items']),
            ]);

            return $plan->fresh(['items.exercise']);
        });
    }
}
