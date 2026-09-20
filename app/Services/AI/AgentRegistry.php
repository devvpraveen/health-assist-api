<?php

namespace App\Services\AI;

use App\Contracts\AI\AgentInterface;
use App\Services\AI\Agents\AbstractAgent;
use InvalidArgumentException;

class AgentRegistry
{
    /** @var array<string, AgentInterface> */
    private array $agents = [];

    public function register(AgentInterface $agent): void
    {
        $this->agents[$agent->key()] = $agent;
    }

    public function get(string $key): AgentInterface
    {
        if (! isset($this->agents[$key])) {
            throw new InvalidArgumentException("Unknown AI agent [{$key}].");
        }

        return $this->agents[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->agents[$key]);
    }

    /**
     * @return list<array{
     *   key: string,
     *   name: string,
     *   description: string,
     *   feature: string,
     *   task_type: string,
     *   layer: string,
     *   maturity: string,
     *   tools: list<string>,
     *   layer_c: bool,
     *   prompt_key: string,
     *   status: string
     * }>
     */
    public function list(): array
    {
        return array_values(array_map(
            function (AgentInterface $agent): array {
                $layer = $agent instanceof AbstractAgent
                    ? $agent->layer()
                    : (LayerCCatalog::LAYERS[$agent->key()] ?? 'business');
                $maturity = $agent instanceof AbstractAgent
                    ? $agent->maturity()
                    : (LayerCCatalog::MATURITY[$agent->key()] ?? 'stub');
                $tools = $agent instanceof AbstractAgent ? $agent->tools() : [];

                return [
                    'key' => $agent->key(),
                    'name' => $agent->name(),
                    'description' => $agent->description(),
                    'feature' => $agent->defaultFeature(),
                    'task_type' => $agent->defaultTaskType(),
                    'layer' => $layer,
                    'maturity' => $maturity,
                    'tools' => $tools,
                    'layer_c' => in_array($agent->key(), LayerCCatalog::LAYER_C_KEYS, true),
                    'prompt_key' => $agent->key(),
                    'status' => $maturity === 'production' ? 'active' : ($maturity === 'beta' ? 'beta' : 'stub'),
                ];
            },
            $this->agents,
        ));
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->agents);
    }
}
