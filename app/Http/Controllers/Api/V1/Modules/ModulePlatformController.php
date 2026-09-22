<?php

namespace App\Http\Controllers\Api\V1\Modules;

use App\Actions\Modules\ActivateModuleAction;
use App\Actions\Modules\AssignPackageToTenantAction;
use App\Actions\Modules\CreateSaaSPackageAction;
use App\Actions\Modules\DeactivateModuleAction;
use App\Actions\Modules\PurchaseModulesAction;
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
use Illuminate\Validation\ValidationException;
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

    public function packages(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('all')
            && $request->user()?->isSuperAdmin();

        $query = Package::query()
            ->with(['modules', 'entitlements', 'limits'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => PackageResource::collection($query->get()),
        ]);
    }

    public function storePackage(Request $request, CreateSaaSPackageAction $action): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:64', 'alpha_dash:ascii'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'clone_from' => ['nullable', 'string', 'exists:packages,key'],
            'module_keys' => ['sometimes', 'array'],
            'module_keys.*' => ['string', 'exists:modules,key'],
            'price_monthly' => ['nullable', 'integer', 'min:0'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'limits' => ['sometimes', 'array'],
            'limits.patients' => ['nullable', 'integer', 'min:0'],
            'limits.staff' => ['nullable', 'integer', 'min:0'],
            'limits.branches' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! isset($validated['price_cents']) && isset($validated['price_monthly'])) {
            $validated['price_cents'] = ((int) $validated['price_monthly']) * 100;
        }

        $package = $action->handle($validated);

        return response()->json([
            'data' => new PackageResource($package),
            'message' => 'SaaS package created.',
        ], 201);
    }

    public function updatePackage(Request $request, string $package): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $model = Package::query()
            ->where('key', $package)
            ->when(ctype_digit($package), fn ($q) => $q->orWhere('id', (int) $package))
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'price_monthly' => ['nullable', 'integer', 'min:0'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $metadata = $model->metadata ?? [];
        if (array_key_exists('price_monthly', $validated)) {
            $metadata['price_monthly'] = $validated['price_monthly'];
            if (! array_key_exists('price_cents', $validated)) {
                $validated['price_cents'] = ((int) $validated['price_monthly']) * 100;
            }
            unset($validated['price_monthly']);
        }

        $model->fill($validated);
        $model->metadata = $metadata ?: null;
        $model->save();

        return response()->json([
            'data' => new PackageResource($model->fresh()->load(['modules', 'entitlements', 'limits'])),
            'message' => 'SaaS package updated.',
        ]);
    }

    public function publicPackages(): JsonResponse
    {
        return $this->packages(request());
    }

    public function publicModules(): JsonResponse
    {
        $modules = PlatformModule::query()
            ->where('is_purchasable', true)
            ->where('status', 'active')
            ->where('price_cents', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => PlatformModuleResource::collection($modules),
        ]);
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

    public function purchaseModules(
        Request $request,
        PurchaseModulesAction $action,
        ResolveTenantModulesAction $resolve,
    ): JsonResponse {
        $this->authorizeManage($request);
        $tenant = $this->currentTenant($request);

        $validated = $request->validate([
            'module_keys' => ['required', 'array', 'min:1'],
            'module_keys.*' => ['string', 'exists:modules,key'],
        ]);

        try {
            $result = $action->handle($tenant, $validated['module_keys']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e instanceof RuntimeException ? 422 : 400);
        }

        return response()->json([
            'data' => [
                'line_items' => $result['line_items'],
                'total_cents' => $result['total_cents'],
                'currency' => $result['currency'],
                'modules' => TenantModuleResource::collection(collect($result['modules'])),
                'active_module_keys' => $resolve->handle($tenant->fresh()),
            ],
            'message' => 'Modules purchased and activated. Payment gateway is not required in this environment.',
            'disclaimer' => 'Module access is billed per module amount and validity. AI remains assistive only.',
        ]);
    }

    public function updateModule(Request $request, string $moduleKey): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $module = PlatformModule::query()->where('key', $moduleKey)->firstOrFail();

        $validated = $request->validate([
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'deprecated'])],
        ]);

        $module->fill($validated);
        $module->save();

        return response()->json([
            'data' => new PlatformModuleResource($module->fresh()),
            'message' => 'Module pricing updated.',
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
