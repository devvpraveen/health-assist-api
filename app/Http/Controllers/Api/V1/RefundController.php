<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\ProcessRefundAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;

class RefundController extends Controller
{
    public function store(StoreRefundRequest $request, ProcessRefundAction $action): JsonResponse
    {
        $refund = $action->handle($request->validated());

        return (new RefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Refund $refund): RefundResource
    {
        $this->authorize('view', $refund);

        return new RefundResource($refund);
    }
}
