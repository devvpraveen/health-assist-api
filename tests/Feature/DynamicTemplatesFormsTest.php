<?php

namespace Tests\Feature;

use App\Models\ContentTemplate;
use App\Models\FormDefinition;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\DynamicTemplatesFormsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class DynamicTemplatesFormsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DynamicTemplatesFormsSeeder::class);
    }

    public function test_lists_platform_templates_and_renders_preview(): void
    {
        [$user] = $this->createTenantUserWithOrg('Template Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $list = $this->getJson('/api/v1/templates?type=assessment&published_only=1')
            ->assertOk();

        $keys = collect($list->json('data'))->pluck('key')->all();
        $this->assertContains('assessment_summary', $keys);

        $template = ContentTemplate::query()->where('key', 'assessment_summary')->firstOrFail();

        $this->postJson("/api/v1/templates/{$template->id}/preview", [
            'context' => [
                'patient' => ['name' => 'Ada'],
                'assessment' => [
                    'chief_complaint' => 'Knee pain',
                    'summary' => 'Improving',
                    'template_key' => 'mobility',
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.template_key', 'assessment_summary')
            ->assertJsonFragment(['rendered' => "Assessment for Ada\nChief complaint: Knee pain\nSummary: Improving"]);
    }

    public function test_tenant_can_publish_form_and_submit_validated_payload(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Form Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $create = $this->postJson('/api/v1/forms', [
            'key' => 'intake_basic',
            'type' => 'patient_intake',
            'module_key' => 'patients',
            'name' => 'Basic intake',
            'publish' => true,
            'schema' => [
                'fields' => [
                    ['key' => 'reason', 'type' => 'text', 'required' => true],
                    ['key' => 'pain_score', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 10],
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.key', 'intake_basic');

        $formId = $create->json('data.id');
        $patient = Patient::factory()->forTenant($tenant)->create();

        $this->postJson("/api/v1/forms/{$formId}/submissions", [
            'patient_id' => $patient->id,
            'payload' => ['pain_score' => 3],
        ])->assertStatus(422);

        $this->postJson("/api/v1/forms/{$formId}/submissions", [
            'patient_id' => $patient->id,
            'payload' => ['reason' => 'Follow-up', 'pain_score' => 3],
        ])
            ->assertCreated()
            ->assertJsonPath('data.payload.reason', 'Follow-up')
            ->assertJsonPath('data.payload.pain_score', 3);
    }

    public function test_assessment_create_validates_against_published_form_schema(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Assess Form Clinic');
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->assertTrue(
            FormDefinition::query()->where('key', 'mobility')->where('status', 'published')->exists()
        );

        $this->postJson('/api/v1/patients/'.$patient->id.'/assessments', [
            'template_key' => 'mobility',
            'assessed_at' => '2026-09-01T10:00:00Z',
            'findings' => ['rom' => 90],
        ])->assertStatus(422);

        $this->postJson('/api/v1/patients/'.$patient->id.'/assessments', [
            'template_key' => 'mobility',
            'assessed_at' => '2026-09-01T10:00:00Z',
            'findings' => ['pain_score' => 6, 'rom' => 90],
        ])
            ->assertCreated()
            ->assertJsonPath('data.template_key', 'mobility')
            ->assertJsonPath('data.findings.pain_score', 6);
    }
}
