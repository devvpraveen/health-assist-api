<?php

namespace Database\Factories;

use App\Models\EmailWorkflow;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmailWorkflow> */
class EmailWorkflowFactory extends Factory
{
    protected $model = EmailWorkflow::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $key = 'wf_'.Str::random(6);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'tenant_key' => 'tenant:pending',
            'key' => $key,
            'name' => 'Workflow '.$key,
            'trigger' => EmailWorkflow::TRIGGER_LEAD_CREATED,
            'is_active' => true,
            'steps' => [
                ['subject' => 'Welcome', 'body' => 'Thanks for joining Health Assist.', 'delay_seconds' => 0],
            ],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (EmailWorkflow $workflow): void {
            if ($workflow->tenant_key === 'tenant:pending' && $workflow->tenant_id) {
                $workflow->forceFill(['tenant_key' => 'tenant:'.$workflow->tenant_id])->saveQuietly();
            }
        });
    }
}
