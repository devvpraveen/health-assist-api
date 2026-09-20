<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Tenant;
use App\Services\AI\DTO\RoutedModel;
use Illuminate\Support\Arr;

class ModelRouter
{
    public function route(string $taskType, ?int $tenantId = null, ?string $modelHint = null): RoutedModel
    {
        if (filled($modelHint)) {
            $fromHint = $this->fromModelHint((string) $modelHint, $taskType);
            if ($fromHint !== null) {
                return $fromHint;
            }

            $config = $this->configForTask($taskType);

            return new RoutedModel(
                provider: (string) ($config['provider'] ?? config('ai.default_provider', 'mock')),
                model: (string) $modelHint,
                modelVersion: (string) ($config['model_version'] ?? '1'),
                taskType: $taskType,
                config: $this->mergeGenerationConfig(
                    Arr::get($config, 'config'),
                ),
            );
        }

        $tenantOverride = $this->tenantOverride($taskType, $tenantId);
        if ($tenantOverride !== null) {
            return $tenantOverride;
        }

        $fromDb = $this->fromRegistry($taskType);
        if ($fromDb !== null) {
            return $fromDb;
        }

        $fromCustom = $this->fromCustomModelsConfig($taskType);
        if ($fromCustom !== null) {
            return $fromCustom;
        }

        $config = $this->configForTask($taskType);

        return new RoutedModel(
            provider: (string) ($config['provider'] ?? config('ai.default_provider', 'mock')),
            model: (string) ($config['model'] ?? 'mock-chat'),
            modelVersion: (string) ($config['model_version'] ?? '1'),
            taskType: $taskType,
            config: $this->mergeGenerationConfig(
                Arr::get($config, 'config'),
            ),
        );
    }

    /**
     * @return array{provider?: string, model?: string, model_version?: string, config?: array<string, mixed>}
     */
    private function configForTask(string $taskType): array
    {
        /** @var array{provider?: string, model?: string, model_version?: string, config?: array<string, mixed>} */
        return (array) config("ai.task_models.{$taskType}", config('ai.task_models.general_conversation', []));
    }

    private function tenantOverride(string $taskType, ?int $tenantId): ?RoutedModel
    {
        if ($tenantId === null) {
            return null;
        }

        $tenant = Tenant::query()->with('settings')->find($tenantId);
        $models = Arr::get($tenant?->settings?->settings ?? [], 'ai.models', []);

        if (! is_array($models) || ! isset($models[$taskType]) || ! is_array($models[$taskType])) {
            return null;
        }

        $override = $models[$taskType];

        if (! isset($override['model'])) {
            return null;
        }

        $modelKey = (string) $override['model'];
        $registryMatch = $this->findRegistryModel($modelKey);
        $resolvedModel = $modelKey;
        $versionConfig = null;
        $modelConfig = is_array($override['config'] ?? null) ? $override['config'] : [];

        if ($registryMatch !== null) {
            $resolvedModel = (string) ($registryMatch->external_model_id ?: $registryMatch->key);
            $version = null;
            if (isset($override['model_version'])) {
                $version = $registryMatch->versions->firstWhere('version', (string) $override['model_version']);
            }
            $version ??= $registryMatch->versions->firstWhere('is_default', true)
                ?? $registryMatch->versions->first();
            $versionConfig = is_array($version?->config) ? $version->config : null;
            $modelConfig = $this->mergeConfigLayers(
                is_array($registryMatch->config) ? $registryMatch->config : [],
                $versionConfig,
                $modelConfig,
            );
        }

        return new RoutedModel(
            provider: (string) ($override['provider'] ?? $registryMatch?->provider?->key ?? config('ai.default_provider', 'mock')),
            model: $resolvedModel,
            modelVersion: (string) ($override['model_version'] ?? '1'),
            taskType: $taskType,
            config: $this->mergeGenerationConfig($modelConfig),
        );
    }

    private function fromRegistry(string $taskType): ?RoutedModel
    {
        $defaultProvider = (string) config('ai.default_provider', 'mock');

        $models = AiModel::query()
            ->with(['provider', 'versions'])
            ->where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_custom')
            ->orderBy('id')
            ->get()
            ->filter(function (AiModel $candidate) use ($taskType): bool {
                $types = $candidate->task_types ?? [];

                return is_array($types) && in_array($taskType, $types, true);
            })
            ->values();

        if ($models->isEmpty()) {
            return null;
        }

        $preferred = $models->first(function (AiModel $candidate) use ($defaultProvider): bool {
            $providerKey = (string) ($candidate->provider?->key ?? '');
            $providerDriver = (string) ($candidate->provider?->driver ?? '');

            return $providerKey === $defaultProvider || $providerDriver === $defaultProvider;
        });

        return $this->routedFromAiModel($preferred ?? $models->first(), $taskType);
    }

    private function fromModelHint(string $modelHint, string $taskType): ?RoutedModel
    {
        $registryMatch = $this->findRegistryModel($modelHint);
        if ($registryMatch !== null) {
            return $this->routedFromAiModel($registryMatch, $taskType);
        }

        /** @var array<string, array<string, mixed>> $customModels */
        $customModels = (array) config('ai.custom_models', []);
        if (isset($customModels[$modelHint]) && is_array($customModels[$modelHint])) {
            $entry = $customModels[$modelHint];

            return new RoutedModel(
                provider: (string) ($entry['provider'] ?? config('ai.default_provider', 'mock')),
                model: (string) ($entry['model'] ?? $modelHint),
                modelVersion: (string) ($entry['model_version'] ?? '1'),
                taskType: $taskType,
                config: $this->mergeGenerationConfig(
                    Arr::get($entry, 'config'),
                ),
            );
        }

        return null;
    }

    private function fromCustomModelsConfig(string $taskType): ?RoutedModel
    {
        /** @var array<string, array<string, mixed>> $customModels */
        $customModels = (array) config('ai.custom_models', []);

        foreach ($customModels as $alias => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $types = $entry['task_types'] ?? [];
            if (! is_array($types) || ! in_array($taskType, $types, true)) {
                continue;
            }

            return new RoutedModel(
                provider: (string) ($entry['provider'] ?? config('ai.default_provider', 'mock')),
                model: (string) ($entry['model'] ?? $alias),
                modelVersion: (string) ($entry['model_version'] ?? '1'),
                taskType: $taskType,
                config: $this->mergeGenerationConfig(
                    Arr::get($entry, 'config'),
                ),
            );
        }

        return null;
    }

    private function findRegistryModel(string $keyOrExternalId): ?AiModel
    {
        return AiModel::query()
            ->with(['provider', 'versions'])
            ->where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->where(function ($q) use ($keyOrExternalId): void {
                $q->where('key', $keyOrExternalId)
                    ->orWhere('external_model_id', $keyOrExternalId);
            })
            ->orderByDesc('is_custom')
            ->first();
    }

    private function routedFromAiModel(AiModel $model, string $taskType, ?string $versionHint = null): RoutedModel
    {
        $version = null;
        if ($versionHint !== null) {
            $version = $model->versions->firstWhere('version', $versionHint);
        }
        $version ??= $model->versions->firstWhere('is_default', true)
            ?? $model->versions->first();

        $resolvedModel = (string) ($model->external_model_id ?: $model->key);

        return new RoutedModel(
            provider: (string) $model->provider->key,
            model: $resolvedModel,
            modelVersion: (string) ($version?->version ?? '1'),
            taskType: $taskType,
            config: $this->mergeGenerationConfig(
                is_array($model->config) ? $model->config : [],
                is_array($version?->config) ? $version->config : [],
            ),
        );
    }

    /**
     * @param  array<string, mixed>|null  ...$layers
     * @return array<string, mixed>
     */
    private function mergeGenerationConfig(?array ...$layers): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = (array) config('ai.generation_defaults', []);

        return $this->mergeConfigLayers($defaults, ...$layers);
    }

    /**
     * @param  array<string, mixed>|null  ...$layers
     * @return array<string, mixed>
     */
    private function mergeConfigLayers(?array ...$layers): array
    {
        $merged = [];

        foreach ($layers as $layer) {
            if (! is_array($layer) || $layer === []) {
                continue;
            }

            $merged = array_merge($merged, $layer);
        }

        return $merged;
    }
}
