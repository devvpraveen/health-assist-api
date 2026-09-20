<?php

namespace App\Services\AI;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TenantAiModelSettings
{
    /**
     * @return array{default_provider: string|null, models: array<string, array<string, mixed>>}
     */
    public function get(?int $tenantId): array
    {
        if ($tenantId === null) {
            return $this->empty();
        }

        $tenant = Tenant::query()->with('settings')->find($tenantId);
        $ai = Arr::get($tenant?->settings?->settings ?? [], 'ai', []);

        if (! is_array($ai)) {
            return $this->empty();
        }

        $models = $ai['models'] ?? [];

        return [
            'default_provider' => isset($ai['default_provider']) ? (string) $ai['default_provider'] : null,
            'models' => is_array($models) ? $models : [],
        ];
    }

    /**
     * @param  array{default_provider?: string|null, models?: array<string, array<string, mixed>>}  $payload
     * @return array{default_provider: string|null, models: array<string, array<string, mixed>>}
     */
    public function put(int $tenantId, array $payload): array
    {
        $validated = $this->validate($payload);

        $setting = TenantSetting::query()->firstOrNew(['tenant_id' => $tenantId]);
        $settings = is_array($setting->settings) ? $setting->settings : [];
        $ai = is_array($settings['ai'] ?? null) ? $settings['ai'] : [];

        if (array_key_exists('default_provider', $validated)) {
            $ai['default_provider'] = $validated['default_provider'];
        }

        if (array_key_exists('models', $validated)) {
            $ai['models'] = $validated['models'];
        }

        $settings['ai'] = $ai;
        $setting->tenant_id = $tenantId;
        $setting->settings = $settings;
        $setting->save();

        return [
            'default_provider' => isset($ai['default_provider']) ? (string) $ai['default_provider'] : null,
            'models' => is_array($ai['models'] ?? null) ? $ai['models'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{default_provider?: string|null, models?: array<string, array<string, mixed>>}
     */
    public function validate(array $payload): array
    {
        $allowedTasks = (array) config('ai.allowed_task_types', []);
        $allowedDrivers = (array) config('ai.allowed_drivers', []);
        $errors = [];

        $result = [];

        if (array_key_exists('default_provider', $payload)) {
            $provider = $payload['default_provider'];
            if ($provider !== null && $provider !== '' && ! in_array($provider, $allowedDrivers, true)
                && ! array_key_exists((string) $provider, (array) config('ai.providers', []))) {
                $errors['default_provider'] = ['The selected default provider is invalid.'];
            } else {
                $result['default_provider'] = $provider !== null && $provider !== '' ? (string) $provider : null;
            }
        }

        if (array_key_exists('models', $payload)) {
            if (! is_array($payload['models'])) {
                $errors['models'] = ['The models field must be an array.'];
            } else {
                $models = [];
                foreach ($payload['models'] as $taskType => $override) {
                    if (! in_array($taskType, $allowedTasks, true)) {
                        $errors["models.{$taskType}"] = ['The selected task type is invalid.'];

                        continue;
                    }

                    if (! is_array($override)) {
                        $errors["models.{$taskType}"] = ['Model override must be an object.'];

                        continue;
                    }

                    if (! isset($override['model']) || ! is_string($override['model']) || $override['model'] === '') {
                        $errors["models.{$taskType}.model"] = ['A model key is required.'];

                        continue;
                    }

                    if (isset($override['config']) && is_array($override['config']) && array_key_exists('api_key', $override['config'])) {
                        $errors["models.{$taskType}.config"] = ['Use env / provider secrets, not model config'];

                        continue;
                    }

                    $entry = [
                        'model' => (string) $override['model'],
                    ];

                    if (isset($override['provider'])) {
                        $entry['provider'] = (string) $override['provider'];
                    }

                    if (isset($override['model_version'])) {
                        $entry['model_version'] = (string) $override['model_version'];
                    }

                    if (isset($override['config']) && is_array($override['config'])) {
                        $entry['config'] = $override['config'];
                    }

                    $models[$taskType] = $entry;
                }

                $result['models'] = $models;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $result;
    }

    /**
     * @return array{default_provider: null, models: array{}}
     */
    private function empty(): array
    {
        return [
            'default_provider' => null,
            'models' => [],
        ];
    }
}
