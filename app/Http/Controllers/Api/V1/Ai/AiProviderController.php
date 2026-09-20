<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ai\StoreAiProviderRequest;
use App\Http\Requests\Api\V1\Ai\UpdateAiProviderRequest;
use App\Http\Resources\Ai\AiProviderResource;
use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AiProviderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->ensureCanManage($request->user());

        $providers = AiProvider::query()
            ->orderBy('key')
            ->paginate();

        return AiProviderResource::collection($providers);
    }

    public function store(StoreAiProviderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $provider = AiProvider::query()->create($data);

        return (new AiProviderResource($provider))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAiProviderRequest $request, AiProvider $provider): AiProviderResource
    {
        $provider->update($request->validated());

        return new AiProviderResource($provider->fresh());
    }

    private function ensureCanManage(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('ai.manage')),
            403,
        );
    }
}
