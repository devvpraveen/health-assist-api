<?php

namespace App\Actions\Clinical;

use App\Models\Exercise;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateExerciseAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Exercise $exercise, array $data): Exercise
    {
        return DB::transaction(function () use ($exercise, $data): Exercise {
            if ($exercise->tenant_id === null) {
                throw ValidationException::withMessages([
                    'exercise' => ['System exercises cannot be updated.'],
                ]);
            }

            if (isset($data['name']) && ! isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $exercise->update(collect($data)->only([
                'name',
                'slug',
                'category',
                'instructions',
                'contraindications',
                'default_duration_seconds',
                'default_sets',
                'default_reps',
                'difficulty',
                'media_url',
                'status',
            ])->all());

            $this->auditLogger->log('clinical.exercise.updated', $exercise, [
                'exercise_uuid' => $exercise->uuid,
            ]);

            return $exercise->fresh();
        });
    }
}
