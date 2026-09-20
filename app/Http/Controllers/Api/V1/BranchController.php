<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Branches\CreateBranchAction;
use App\Actions\Branches\UpdateBranchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBranchRequest;
use App\Http\Requests\Api\V1\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BranchController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Branch::class);

        return BranchResource::collection(
            Branch::query()->with('organization')->latest()->paginate()
        );
    }

    public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
    {
        $this->authorize('create', Branch::class);

        $branch = $action->handle($request->validated());

        return (new BranchResource($branch->load('organization')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Branch $branch): BranchResource
    {
        $this->authorize('view', $branch);

        return new BranchResource($branch->load('organization'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch, UpdateBranchAction $action): BranchResource
    {
        $this->authorize('update', $branch);

        return new BranchResource(
            $action->handle($branch, $request->validated())->load('organization')
        );
    }

    public function destroy(Branch $branch): Response
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return response()->noContent();
    }
}
