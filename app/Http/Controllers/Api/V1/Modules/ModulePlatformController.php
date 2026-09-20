<?php

namespace App\Http\Controllers\Api\V1\Modules;

use App\Actions\Modules\ActivateModuleAction;
use App\Actions\Modules\AssignPackageToTenantAction;
use App\Actions\Modules\DeactivateModuleAction;
use App\Actions\Modules\ResolveEntitlementsAction;
use App\Actions\Modules\ResolveTenantModulesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Modules\PackageResource;
use App\Http\Resources\Modules\PlatformModuleResource;
use App\Http\Resources\Modules\TenantModuleResource;
use App\Models\Package;
use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\TenantSubscription;
use App\Models\UsageCounter;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ModulePlatformController extends Controller
{
    public function catalog(): JsonResponse
    {
        $modules = PlatformModule::query()->orderBy('key')->get();

        return response()->json([
            'data' => PlatformModuleResource::collection($modules),
        ]);
    }

    public function packages(): JsonResponse
    {
        $packages = Package::query()
            ->with(['modules', 'entitlements', 'limits'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => PackageResource::collection($packages),
        ]);
    }

    public function publicPackages(): JsonResponse
    {
        return $this->packages();
    }

    public function assignSubscription(
        Request $request,
        AssignPackageToTenantAction $action,
        ResolveTenantModulesAction $resolve,
    ): JsonResponse {
        $this->authorizeManage($request);
        $tenant = $this->currentTenant($request);

        $validated = $request->validate([
            'package_key' => [
                'required',
                'string',
                Rule::exists('packages', 'key')->where(fn ($q) => $q->where('is_active', true)),
            ],
        ]);

        $package = Package::query()
            ->where('key', $validated['package_key'])
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $subscription = $action->handle($tenant, $package, true);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e instanceof RuntimeException ? 422 : 400);
        }

        $activeKeys = $resolve->handle($tenant->fresh());

        return response()->json([
            'data' => [
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'package' => $subscription->package ? [
                        'key' => $subscription->package->key,
                        'name' => $subscription->package->name,
                    ] : null,
                    'renews_at' => $subscription->renews_at,
                ],
                'active_module_keys' => $activeKeys,
            ],
            'message' => "Package [{$package->key}] assigned. Payment checkout is not required in this environment.",
            'disclaimer' => 'Plan changes update module access only. Clinical data is retained. AI remains assistive only.',
        ]);
    }

    public function tenantModules(Request $request, ResolveTenantModulesAction $resolve): JsonResponse
    {
        $tenant = $this->currentTenant($request);
        $activeKeys = $resolve->handle($tenant);

        $rows = TenantModule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('module')
            ->get();

        $subscription = TenantSubscription::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->with('package')
            ->first();

        return response()->json([
            'data' => [
                'active_module_keys' => $activeKeys,
                'modules' => TenantModuleResource::collection($rows),
                'subscription' => $subscription ? [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'package' => $subscription->package ? [
                        'key' => $subscription->package->key,
                        'name' => $subscription->package->name,
                    ] : null,
                    'renews_at' => $subscription->renews_at,
                ] : null,
            ],
        ]);
    }

    public function activate(
        Request $request,
        string $moduleKey,
        ActivateModuleAction $action,
    ): JsonResponse {
        $this->authorizeManage($request);
        $tenant = $this->currentTenant($request);

        try {
            $row = $action->handle($tenant, $moduleKey);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e instanceof RuntimeException ? 422 : 400);
        }

        return response()->json([
            'data' => new TenantModuleResource($row),
            'message' => "Module [{$moduleKey}] activated.",
        ]);
    }

    public function deactivate(
        Request $request,
        string $moduleKey,
        DeactivateModuleAction $action,
    ): JsonResponse {
        $this->authorizeManage($request);
        $tenant = $this->currentTenant($request);

        try {
            $row = $action->handle($tenant, $moduleKey);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e instanceof RuntimeException ? 422 : 400);
        }

        return response()->json([
            'data' => new TenantModuleResource($row),
            'message' => "Module [{$moduleKey}] deactivated. Historical data retained.",
        ]);
    }

    public function entitlements(Request $request, ResolveEntitlementsAction $action): JsonResponse
    {
        $tenant = $this->currentTenant($request);
        $entitlements = $action->handle($tenant);

        $usage = UsageCounter::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('period_key', now()->format('Y-m'))
            ->get()
            ->mapWithKeys(fn (UsageCounter $c) => [$c->key => $c->count]);

        return response()->json([
            'data' => [
                'entitlements' => $entitlements,
                'usage' => $usage,
                'period_key' => now()->format('Y-m'),
            ],
        ]);
    }

    private function currentTenant(Request $request): Tenant
    {
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        abort_if($tenantId === null, 403, 'Tenant context required.');

        return Tenant::query()->findOrFail($tenantId);
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || $user->hasPermission('organizations.manage') || $user->hasPermission('tenants.manage')),
            403,
        );
    }
}
