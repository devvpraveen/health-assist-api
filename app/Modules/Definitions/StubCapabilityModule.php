<?php

namespace App\Modules\Definitions;

/**
 * Lightweight stub for modules that are plan-gated before full UX ships.
 */
class StubCapabilityModule extends AbstractModuleDefinition
{
    public function __construct(
        private string $moduleKey,
        private string $moduleName,
        private string $moduleDescription,
        private string $moduleCategory = 'operations',
        /** @var list<string> */
        private array $moduleDependencies = ['core'],
        /** @var list<string> */
        private array $moduleNavigation = [],
    ) {}

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function definition(): array
    {
        return [
            'name' => $this->moduleName,
            'description' => $this->moduleDescription,
            'category' => $this->moduleCategory,
            'metadata' => ['stub' => true],
        ];
    }

    public function dependencies(): array
    {
        return $this->moduleDependencies;
    }

    public function navigation(): array
    {
        return $this->moduleNavigation !== [] ? $this->moduleNavigation : [$this->moduleKey];
    }
}
