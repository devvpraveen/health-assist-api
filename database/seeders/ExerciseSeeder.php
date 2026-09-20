<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExerciseSeeder extends Seeder
{
    /**
     * Seed system exercise catalog.
     */
    public function run(): void
    {
        $exercises = [
            'ankle_pumps' => [
                'name' => 'Ankle Pumps',
                'category' => 'lower_limb',
                'instructions' => 'Point and flex the foot repeatedly to promote circulation.',
                'default_sets' => 3,
                'default_reps' => 20,
            ],
            'quad_sets' => [
                'name' => 'Quad Sets',
                'category' => 'lower_limb',
                'instructions' => 'Tighten the thigh muscle by pressing the knee down into the surface.',
                'default_sets' => 3,
                'default_reps' => 10,
                'default_duration_seconds' => 5,
            ],
            'shoulder_pendulum' => [
                'name' => 'Shoulder Pendulum',
                'category' => 'upper_limb',
                'instructions' => 'Lean forward and gently swing the arm in small circles.',
                'default_duration_seconds' => 60,
                'default_sets' => 2,
            ],
        ];

        foreach ($exercises as $slug => $attributes) {
            Exercise::query()->firstOrCreate(
                ['tenant_key' => 'system', 'slug' => $slug],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => null,
                    'name' => $attributes['name'],
                    'category' => $attributes['category'],
                    'instructions' => $attributes['instructions'],
                    'default_sets' => $attributes['default_sets'] ?? null,
                    'default_reps' => $attributes['default_reps'] ?? null,
                    'default_duration_seconds' => $attributes['default_duration_seconds'] ?? null,
                    'status' => 'active',
                ],
            );
        }
    }
}
