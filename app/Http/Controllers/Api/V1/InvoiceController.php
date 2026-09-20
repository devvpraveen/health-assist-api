<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\AddInvoiceItemAction;
use App\Actions\Billing\CancelInvoiceAction;
use App\Actions\Billing\CreateInvoiceAction;
use App\Actions\Billing\DeleteInvoiceItemAction;
use App\Actions\Billing\IssueInvoiceAction;
use App\Actions\Billing\UpdateInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceItemRequest;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->with('items')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->integer('clinic_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return InvoiceResource::collection($query->paginate());
    }

    public function store(StoreInvoiceRequest $request, CreateInvoiceAction $action): JsonResponse
    {
        $invoice = $action->handle($request->validated());

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice->load('items'));
    }

    public function update(
        UpdateInvoiceRequest $request,
        Invoice $invoice,
        UpdateInvoiceAction $action,
    ): InvoiceResource {
        return new InvoiceResource($action->handle($invoice, $request->validated()));
    }

    public function storeItem(
        StoreInvoiceItemRequest $request,
        Invoice $invoice,
        AddInvoiceItemAction $action,
    ): JsonResponse {
        $item = $action->handle($invoice, $request->validated());

        return (new InvoiceItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function destroyItem(
        Invoice $invoice,
        InvoiceItem $item,
        DeleteInvoiceItemAction $action,
    ): Response {
        $this->authorize('manageItems', $invoice);

        $action->handle($invoice, $item);

        return response()->noContent();
    }

    public function issue(Invoice $invoice, IssueInvoiceAction $action): InvoiceResource
    {
        $this->authorize('issue', $invoice);

        return new InvoiceResource($action->handle($invoice));
    }

    public function cancel(Invoice $invoice, CancelInvoiceAction $action): InvoiceResource
    {
        $this->authorize('cancel', $invoice);

        return new InvoiceResource($action->handle($invoice));
    }
}
