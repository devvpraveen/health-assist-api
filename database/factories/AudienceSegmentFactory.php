<?php

namespace Database\Factories;

use App\Models\AudienceSegment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AudienceSegment> */
class AudienceSegmentFactory extends Factory
{
    protected $model = AudienceSegment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'key' => 'seg_'.Str::random(6),
            'name' => 'Segment',
            'definition' => ['new_user' => true, 'new_user_days' => 30],
        ];
    }
}
