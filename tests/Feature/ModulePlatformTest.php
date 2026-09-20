<?php

namespace Tests\Feature;

use App\Actions\Modules\ActivateModuleAction;
use App\Actions\Modules\AssignPackageToTenantAction;
use App\Actions\Modules\DeactivateModuleAction;
use App\Exceptions\AI\AiQuotaExceededException;
use App\Models\Package;
use App\Models\Patient;
use App\Models\TenantModule;
use App\Models\UsageCounter;
use App\Services\AI\AiUsageQuotaService;
use App\Services\Modules\EntitlementUsageService;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\ModulePlatformSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ModulePlatformTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModulePlatformSeeder::class);
    }

    public function test_professional_package_activates_included_modules(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Mod Clinic');
        $professional = Package::query()->where('key', 'professional')->firstOrFail();

        app(AssignPackageToTenantAction::class)->handle($tenant, $professional, true);

        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'package_id' => $professional->id,
            'status' => 'active',
        ]);

        $this->assertTrue(
            TenantModule::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereHas('module', fn ($q) => $q->where('key', 'health_guide'))
                ->where('status', 'active')
                ->exists()
        );

        $this->assertSame('professional', $tenant->fresh()->plan_code);
    }

    public function test_activate_addon_requires_dependencies_and_deactivate_blocks_dependents(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Deps Clinic');
        $professional = Package::query()->where('key', 'professional')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $professional, true);

        // WhatsApp is addon-eligible on Professional.
        $row = app(ActivateModuleAction::class)->handle($tenant, 'whatsapp');
        $this->assertSame('active', $row->status);

        $this->expectException(RuntimeException::class);
        app(DeactivateModuleAction::class)->handle($tenant, 'patients');
    }

    public function test_middleware_blocks_disabled_module_and_keeps_patient_data(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Gate Clinic');
        $professional = Package::query()->where('key', 'professional')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $professional, true);

        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/patients')->assertOk();

        app(DeactivateModuleAction::class)->handle($tenant, 'wellness'); // no dependents on professional

        // Deactivating unrelated module should not delete patients.
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);

        // Disable whatsapp after activating, then hit route.
        app(ActivateModuleAction::class)->handle($tenant, 'whatsapp');
        app(DeactivateModuleAction::class)->handle($tenant, 'whatsapp');

        $this->getJson('/api/v1/whatsapp/accounts')->assertForbidden();
    }

    public function test_tenant_module_apis(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Api Clinic');
        $professional = Package::query()->where('key', 'professional')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $professional, true);

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/modules')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/packages')->assertOk();
        $this->getJson('/api/v1/tenants/current/modules')
            ->assertOk()
            ->assertJsonPath('data.subscription.package.key', 'professional');
        $this->getJson('/api/v1/tenants/current/entitlements')->assertOk();

        $this->postJson('/api/v1/tenants/current/modules/whatsapp/activate')->assertOk();
        $this->postJson('/api/v1/tenants/current/modules/whatsapp/deactivate')->assertOk();
    }

    public function test_public_packages_catalog_is_available_without_auth(): void
    {
        $this->getJson('/api/v1/public/packages')
            ->assertOk()
            ->assertJsonStructure(['data' => [['key', 'name']]]);
    }

    public function test_self_serve_subscription_assign_upgrades_package(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Upgrade Clinic');
        $starter = Package::query()->where('key', 'starter')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $starter, true);

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->postJson('/api/v1/tenants/current/subscription', [
            'package_key' => 'professional',
        ])
            ->assertOk()
            ->assertJsonPath('data.subscription.package.key', 'professional');

        $this->assertTrue(
            TenantModule::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereHas('module', fn ($q) => $q->where('key', 'health_guide'))
                ->where('status', 'active')
                ->exists()
        );
    }

    public function test_package_downgrade_disables_orphan_package_modules_keeps_addons(): void
    {
        [, , $tenant] = $this->createTenantUserWithOrg('Downgrade Clinic');
        $professional = Package::query()->where('key', 'professional')->firstOrFail();
        $starter = Package::query()->where('key', 'starter')->firstOrFail();

        app(AssignPackageToTenantAction::class)->handle($tenant, $professional, true);
        app(ActivateModuleAction::class)->handle($tenant, 'whatsapp');

        app(AssignPackageToTenantAction::class)->handle($tenant, $starter, true);

        $this->assertTrue(
            TenantModule::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereHas('module', fn ($q) => $q->where('key', 'health_guide'))
                ->where('status', TenantModule::STATUS_DISABLED)
                ->exists()
        );

        $this->assertTrue(
            TenantModule::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereHas('module', fn ($q) => $q->where('key', 'whatsapp'))
                ->where('status', TenantModule::STATUS_ACTIVE)
                ->where('source', TenantModule::SOURCE_ADDON)
                ->exists()
        );
    }

    public function test_ai_message_package_entitlement_blocks_and_increments_usage_counters(): void
    {
        config([
            'ai.metering.enabled' => true,
            'ai.metering.enforce_quotas' => true,
            'ai.metering.monthly_request_limit' => null,
            'ai.metering.monthly_token_limit' => null,
        ]);

        [, , $tenant] = $this->createTenantUserWithOrg('Quota Clinic');
        $starter = Package::query()->where('key', 'starter')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $starter, true);

        $usage = app(EntitlementUsageService::class);
        $usage->increment($tenant->id, EntitlementUsageService::KEY_AI_MESSAGES, 999);
        $this->assertSame(999, $usage->used($tenant->id, EntitlementUsageService::KEY_AI_MESSAGES));

        app(AiUsageQuotaService::class)->recordPackageMessageUsage($tenant->id);
        $this->assertSame(1000, $usage->used($tenant->id, EntitlementUsageService::KEY_AI_MESSAGES));

        try {
            app(AiUsageQuotaService::class)->assertWithinQuota($tenant->id);
            $this->fail('Expected AiQuotaExceededException');
        } catch (AiQuotaExceededException $e) {
            $this->assertSame('package_entitlement', $e->snapshot['reason'] ?? null);
            $this->assertSame(1000, $e->snapshot['requests_limit'] ?? null);
        }
    }

    public function test_report_analysis_returns_429_when_package_report_limit_exceeded(): void
    {
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
        Storage::fake('local');

        [$user, , $tenant] = $this->createTenantUserWithOrg('Report Limit Clinic');
        $professional = Package::query()->where('key', 'professional')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $professional, true);

        UsageCounter::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'key' => EntitlementUsageService::KEY_AI_REPORTS,
            'period_key' => now()->format('Y-m'),
            'count' => 500,
        ]);

        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $upload = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 100, 'application/pdf'),
            'category' => 'laboratory',
        ], ['Accept' => 'application/json'])->assertCreated();

        $documentId = $upload->json('data.id');

        $this->postJson("/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze")
            ->assertStatus(429)
            ->assertJsonPath('entitlement.key', EntitlementUsageService::KEY_AI_REPORTS)
            ->assertJsonPath('entitlement.limit', 500);
    }

    public function test_starter_package_gates_inactive_module_apis(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Gate Apis Clinic');
        $starter = Package::query()->where('key', 'starter')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $starter, true);

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/patients')->assertOk();
        $this->getJson('/api/v1/appointments')->assertOk();
        $this->getJson('/api/v1/providers')->assertOk();

        $this->getJson('/api/v1/invoices')->assertForbidden();
        $this->getJson('/api/v1/whatsapp/accounts')->assertForbidden();
        $this->getJson('/api/v1/crm/leads')->assertForbidden();
        $this->getJson('/api/v1/report-analyses')->assertForbidden();
        $this->getJson('/api/v1/ai/agents')->assertForbidden();
        $this->getJson('/api/v1/health-guide/conversations')->assertForbidden();
    }
}
