<?php

namespace App\Modules\Contracts;

interface HealthAssistModule
{
    public function key(): string;

    /**
     * @return array{
     *   name: string,
     *   slug?: string,
     *   description?: string,
     *   category?: string,
     *   version?: string,
     *   configuration_schema?: array<string, mixed>,
     *   settings_schema?: array<string, mixed>,
     *   metadata?: array<string, mixed>
     * }
     */
    public function definition(): array;

    /**
     * @return list<string>
     */
    public function dependencies(): array;

    /**
     * @return list<string>
     */
    public function optionalDependencies(): array;

    /**
     * @return list<string>
     */
    public function conflicts(): array;

    /**
     * @return list<string>
     */
    public function capabilities(): array;

    /**
     * Permission slug prefixes or exact slugs associated with this module.
     *
     * @return list<string>
     */
    public function permissions(): array;

    /**
     * Navigation / experience module keys this capability unlocks.
     *
     * @return list<string>
     */
    public function navigation(): array;

    /**
     * Route middleware module keys (usually same as key()).
     *
     * @return list<string>
     */
    public function routeModuleKeys(): array;
}
