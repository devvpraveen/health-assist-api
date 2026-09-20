<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\RecordPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        return PaymentResource::collection(
            Payment::query()->with(['receipt', 'invoice'])->latest('id')->paginate()
        );
    }

    public function store(StorePaymentRequest $request, RecordPaymentAction $action): JsonResponse
    {
        $payment = $action->handle($request->validated());

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment->load(['receipt', 'invoice']));
    }
}
