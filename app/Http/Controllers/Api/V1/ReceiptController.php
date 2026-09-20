<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReceiptController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Receipt::class);

        return ReceiptResource::collection(
            Receipt::query()->latest('issued_at')->paginate()
        );
    }

    public function show(Receipt $receipt): ReceiptResource
    {
        $this->authorize('view', $receipt);

        return new ReceiptResource($receipt);
    }
}
