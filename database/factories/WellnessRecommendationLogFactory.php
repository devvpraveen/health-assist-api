<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\WellnessContent;
use App\Models\WellnessRecommendationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WellnessRecommendationLog>
 */
class WellnessRecommendationLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'content_id' => WellnessContent::factory()->published(),
            'score' => fake()->randomFloat(2, 1, 100),
            'reason' => ['interest_match' => true],
            'created_at' => now(),
        ];
    }
}
