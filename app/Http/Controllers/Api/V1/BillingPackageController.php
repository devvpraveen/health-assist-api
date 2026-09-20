<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\CreateBillingPackageAction;
use App\Actions\Billing\UpdateBillingPackageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBillingPackageRequest;
use App\Http\Requests\Api\V1\UpdateBillingPackageRequest;
use App\Http\Resources\BillingPackageResource;
use App\Models\BillingPackage;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BillingPackageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', BillingPackage::class);

        $packages = BillingPackage::query()
            ->orderBy('name')
            ->paginate();

        return BillingPackageResource::collection($packages);
    }

    public function store(StoreBillingPackageRequest $request, CreateBillingPackageAction $action): JsonResponse
    {
        $package = $action->handle($request->validated());

        return (new BillingPackageResource($package))
            ->response()
            ->setStatusCode(201);
    }

    public function show(BillingPackage $billingPackage): BillingPackageResource
    {
        $this->authorize('view', $billingPackage);

        return new BillingPackageResource($billingPackage);
    }

    public function update(
        UpdateBillingPackageRequest $request,
        BillingPackage $billingPackage,
        UpdateBillingPackageAction $action,
    ): BillingPackageResource {
        return new BillingPackageResource($action->handle($billingPackage, $request->validated()));
    }

    public function destroy(BillingPackage $billingPackage, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $billingPackage);

        $auditLogger->log('billing.package.deleted', $billingPackage, [
            'package_uuid' => $billingPackage->uuid,
        ]);

        $billingPackage->delete();

        return response()->noContent();
    }
}
