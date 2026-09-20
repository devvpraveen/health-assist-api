<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\CreateBillingTaxRateAction;
use App\Actions\Billing\UpdateBillingTaxRateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBillingTaxRateRequest;
use App\Http\Requests\Api\V1\UpdateBillingTaxRateRequest;
use App\Http\Resources\BillingTaxRateResource;
use App\Models\BillingTaxRate;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class BillingTaxRateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', BillingTaxRate::class);

        $rates = BillingTaxRate::query()
            ->visibleToTenant(TenantContext::id())
            ->orderBy('name')
            ->paginate();

        return BillingTaxRateResource::collection($rates);
    }

    public function store(StoreBillingTaxRateRequest $request, CreateBillingTaxRateAction $action): JsonResponse
    {
        $taxRate = $action->handle($request->validated());

        return (new BillingTaxRateResource($taxRate))
            ->response()
            ->setStatusCode(201);
    }

    public function show(BillingTaxRate $taxRate): BillingTaxRateResource
    {
        $this->authorize('view', $taxRate);

        return new BillingTaxRateResource($taxRate);
    }

    public function update(
        UpdateBillingTaxRateRequest $request,
        BillingTaxRate $taxRate,
        UpdateBillingTaxRateAction $action,
    ): BillingTaxRateResource {
        return new BillingTaxRateResource($action->handle($taxRate, $request->validated()));
    }

    public function destroy(BillingTaxRate $taxRate, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $taxRate);

        if ($taxRate->tenant_id === null) {
            throw ValidationException::withMessages([
                'tax_rate' => ['System tax rates cannot be deleted.'],
            ]);
        }

        $auditLogger->log('billing.tax_rate.deleted', $taxRate, [
            'tax_rate_uuid' => $taxRate->uuid,
        ]);

        $taxRate->delete();

        return response()->noContent();
    }
}
