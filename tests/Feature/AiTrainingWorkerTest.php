<?php

namespace Tests\Feature;

use App\Models\AiLearningCandidate;
use App\Models\AiModelVersion;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingJob;
use App\Services\AI\Learning\DatasetBuilder;
use App\Services\AI\Learning\ModelRegistryService;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AiTrainingWorkerTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);

        config(['ai.training.driver' => 'mock']);
    }

    public function test_training_job_creates_candidate_not_production(): void
    {
        [$user] = $this->createTenantUserWithOrg('Train Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $dataset = $this->approvedDataset($user->tenant_id, $user->id, deidentified: true);

        $response = $this->postJson('/api/v1/ai/learning/training-jobs', [
            'dataset_id' => $dataset->id,
            'driver' => 'mock',
        ])->assertStatus(202)
            ->assertJsonPath('data.status', AiTrainingJob::STATUS_SUCCEEDED)
            ->assertJsonPath('data.lifecycle_status', 'candidate')
            ->assertJsonPath('data.weights_updated_in_production', false);

        $version = AiModelVersion::query()->find($response->json('data.model_version_id'));
        $this->assertNotNull($version);
        $this->assertSame('candidate', $version->lifecycle_status);
        $this->assertFalse($version->is_default);
        $this->assertFalse((bool) data_get($version->lifecycle_meta, 'auto_promoted_to_production'));

        $this->expectException(\RuntimeException::class);
        app(ModelRegistryService::class)->setLifecycle($version, 'production');
    }

    public function test_training_rejects_non_deidentified_dataset(): void
    {
        [$user] = $this->createTenantUserWithOrg('Pii Train Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $dataset = $this->approvedDataset($user->tenant_id, $user->id, deidentified: false);

        $this->postJson('/api/v1/ai/learning/training-jobs', [
            'dataset_id' => $dataset->id,
        ])->assertStatus(422);
    }

    public function test_training_rejects_empty_dataset(): void
    {
        [$user] = $this->createTenantUserWithOrg('Empty Train Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $dataset = AiTrainingDataset::query()->create([
            'tenant_id' => $user->tenant_id,
            'key' => 'empty-set',
            'name' => 'Empty',
            'version' => '1',
            'status' => 'ready',
            'item_count' => 0,
        ]);

        $this->postJson('/api/v1/ai/learning/training-jobs', [
            'dataset_id' => $dataset->id,
        ])->assertStatus(422);
    }

    public function test_python_worker_writes_candidate_manifest(): void
    {
        $script = dirname(base_path(), 1).'/training/worker.py';
        if (! is_file($script)) {
            $this->markTestSkipped('Python worker script missing.');
        }

        $input = tempnam(sys_get_temp_dir(), 'ha-train-in-');
        $output = sys_get_temp_dir().'/ha-train-out-'.uniqid('', true).'.json';
        file_put_contents($input, json_encode([
            'prompt' => 'knee pain on stairs',
            'completion' => 'Mechanical discomfort; escalate if swelling worsens.',
            'agent' => 'clinical',
            'deidentified' => true,
        ])."\n");

        $code = 0;
        $stderr = [];
        exec('python3 '.escapeshellarg($script).' --input '.escapeshellarg($input).' --output '.escapeshellarg($output).' 2>&1', $stderr, $code);

        $this->assertSame(0, $code, implode("\n", $stderr));
        $result = json_decode((string) file_get_contents($output), true);
        $this->assertTrue($result['ok'] ?? false);
        $this->assertFalse($result['weights_updated_in_production'] ?? true);
        $this->assertSame('candidate', $result['lifecycle_suggested'] ?? null);
        $this->assertSame(1.0, $result['metrics']['safety'] ?? null);

        @unlink($input);
        @unlink($output);
    }

    private function approvedDataset(int $tenantId, int $userId, bool $deidentified): AiTrainingDataset
    {
        AiLearningCandidate::query()->create([
            'tenant_id' => $tenantId,
            'agent' => 'clinical',
            'status' => AiLearningCandidate::STATUS_APPROVED,
            'input_redacted' => 'Right knee pain on stairs',
            'original_output' => 'Maybe strain',
            'corrected_output' => 'Mechanical low back? No — mechanical knee pain; review if swelling.',
            'deidentified' => $deidentified,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);

        return app(DatasetBuilder::class)->buildFromApproved(
            key: 'clinical-corrections',
            name: 'Clinical corrections',
            agent: 'clinical',
            tenantId: $tenantId,
            limit: 10,
        );
    }
}
