<?php

namespace Database\Factories;

use App\Models\EmailSubscription;
use App\Models\EmailWorkflow;
use App\Models\EmailWorkflowRun;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmailWorkflowRun> */
class EmailWorkflowRunFactory extends Factory
{
    protected $model = EmailWorkflowRun::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'workflow_id' => EmailWorkflow::factory(),
            'subscription_id' => EmailSubscription::factory(),
            'status' => EmailWorkflowRun::STATUS_PENDING,
            'current_step' => 0,
            'meta' => null,
        ];
    }
}
