<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ai\StoreAiModelRequest;
use App\Http\Requests\Api\V1\Ai\StoreAiModelVersionRequest;
use App\Http\Requests\Api\V1\Ai\UpdateAiModelRequest;
use App\Http\Requests\Api\V1\Ai\UpdateAiModelVersionRequest;
use App\Http\Resources\Ai\AiModelResource;
use App\Http\Resources\Ai\AiModelVersionResource;
use App\Models\AiModel;
use App\Models\AiModelVersion;
use App\Models\AiProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiModelManageController extends Controller
{
    public function store(StoreAiModelRequest $request): JsonResponse
    {
        $data = $request->validated();
        $provider = $this->resolveProvider($data);

        $existing = AiModel::query()
            ->where('provider_id', $provider->id)
            ->where('key', $data['key'])
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'key' => ['A model with this key already exists for the provider.'],
            ]);
        }

        $model = DB::transaction(function () use ($data, $provider): AiModel {
            $model = AiModel::query()->create([
                'provider_id' => $provider->id,
                'key' => $data['key'],
                'name' => $data['name'],
                'task_types' => $data['task_types'],
                'config' => $data['config'] ?? null,
                'is_custom' => $data['is_custom'] ?? true,
                'external_model_id' => $data['external_model_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            AiModelVersion::query()->create([
                'model_id' => $model->id,
                'version' => $data['version'] ?? '1',
                'is_default' => true,
                'config' => $data['version_config'] ?? null,
            ]);

            return $model->load(['provider', 'versions']);
        });

        return (new AiModelResource($model))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAiModelRequest $request, AiModel $model): AiModelResource
    {
        $data = $request->validated();

        if (isset($data['provider_id']) || isset($data['provider_key'])) {
            $data['provider_id'] = $this->resolveProvider($data)->id;
            unset($data['provider_key']);
        }

        if (isset($data['key'])) {
            $duplicate = AiModel::query()
                ->where('provider_id', $data['provider_id'] ?? $model->provider_id)
                ->where('key', $data['key'])
                ->where('id', '!=', $model->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'key' => ['A model with this key already exists for the provider.'],
                ]);
            }
        }

        $model->update($data);

        return new AiModelResource($model->fresh()->load(['provider', 'versions']));
    }

    public function storeVersion(StoreAiModelVersionRequest $request, AiModel $model): JsonResponse
    {
        $data = $request->validated();

        $version = DB::transaction(function () use ($data, $model): AiModelVersion {
            if (($data['is_default'] ?? false) === true) {
                $model->versions()->update(['is_default' => false]);
            }

            return AiModelVersion::query()->create([
                'model_id' => $model->id,
                'version' => $data['version'],
                'is_default' => $data['is_default'] ?? false,
                'config' => $data['config'] ?? null,
            ]);
        });

        return (new AiModelVersionResource($version))
            ->response()
            ->setStatusCode(201);
    }

    public function updateVersion(UpdateAiModelVersionRequest $request, AiModelVersion $version): AiModelVersionResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $version): void {
            if (($data['is_default'] ?? false) === true) {
                AiModelVersion::query()
                    ->where('model_id', $version->model_id)
                    ->where('id', '!=', $version->id)
                    ->update(['is_default' => false]);
            }

            $version->update($data);
        });

        return new AiModelVersionResource($version->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveProvider(array $data): AiProvider
    {
        if (isset($data['provider_id'])) {
            return AiProvider::query()->findOrFail($data['provider_id']);
        }

        return AiProvider::query()->where('key', $data['provider_key'])->firstOrFail();
    }
}
