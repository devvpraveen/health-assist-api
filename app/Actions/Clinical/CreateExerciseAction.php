<?php

namespace App\Actions\Clinical;

use App\Models\Exercise;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateExerciseAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Exercise
    {
        return DB::transaction(function () use ($data): Exercise {
            $name = $data['name'];
            $slug = $data['slug'] ?? Str::slug($name);
            $tenantId = TenantContext::id();

            $exercise = Exercise::query()->create([
                'tenant_id' => $tenantId,
                'tenant_key' => $tenantId === null ? 'system' : (string) $tenantId,
                'name' => $name,
                'slug' => $slug,
                'category' => $data['category'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'contraindications' => $data['contraindications'] ?? null,
                'default_duration_seconds' => $data['default_duration_seconds'] ?? null,
                'default_sets' => $data['default_sets'] ?? null,
                'default_reps' => $data['default_reps'] ?? null,
                'difficulty' => $data['difficulty'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->auditLogger->log('clinical.exercise.created', $exercise, [
                'exercise_uuid' => $exercise->uuid,
            ]);

            return $exercise;
        });
    }
}
