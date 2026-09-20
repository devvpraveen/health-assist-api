<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenantContext;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_forging_tenant_id_in_request_body_does_not_change_context(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenantA)->create();

        $request = Request::create('/api/v1/organizations', 'POST', [
            'tenant_id' => $tenantB->id,
            'name' => 'Forged Org',
        ]);
        $request->headers->set('X-Tenant-Id', (string) $tenantB->id);
        $request->setUserResolver(fn () => $user);

        TenantContext::clear();

        $middleware = new EnsureTenantContext;
        $middleware->handle($request, function () {
            return response('ok');
        });

        $this->assertSame($tenantA->id, TenantContext::id());
        $this->assertNotSame($tenantB->id, TenantContext::id());
    }

    public function test_super_admin_without_tenant_gets_null_context(): void
    {
        $user = $this->createSuperAdminUser();

        $request = Request::create('/api/v1/tenants', 'GET');
        $request->setUserResolver(fn () => $user);

        TenantContext::clear();

        (new EnsureTenantContext)->handle($request, fn () => response('ok'));

        $this->assertNull(TenantContext::id());
    }

    public function test_super_admin_can_switch_tenant_via_header(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->createSuperAdminUser();

        $request = Request::create('/api/v1/clinics', 'GET');
        $request->headers->set('X-Tenant-UUID', $tenant->uuid);
        $request->setUserResolver(fn () => $user->fresh(['roles']));

        TenantContext::clear();

        (new EnsureTenantContext)->handle($request, fn () => response('ok'));

        $this->assertSame($tenant->id, TenantContext::id());
    }
}
