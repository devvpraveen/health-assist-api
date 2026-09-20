<?php

namespace App\Services\Modules;

use App\Modules\Contracts\HealthAssistModule;
use App\Modules\Definitions\AiModule;
use App\Modules\Definitions\AppointmentsModule;
use App\Modules\Definitions\BillingModule;
use App\Modules\Definitions\ClinicalModule;
use App\Modules\Definitions\CoreModule;
use App\Modules\Definitions\CrmModule;
use App\Modules\Definitions\HealthGuideModule;
use App\Modules\Definitions\MarketingModule;
use App\Modules\Definitions\MedicationsModule;
use App\Modules\Definitions\PatientsModule;
use App\Modules\Definitions\ProvidersModule;
use App\Modules\Definitions\ReportsModule;
use App\Modules\Definitions\SeoModule;
use App\Modules\Definitions\WellnessModule;
use App\Modules\Definitions\WhatsappModule;
use InvalidArgumentException;

class ModuleDefinitionRegistry
{
    /** @var array<string, class-string<HealthAssistModule>> */
    private array $definitions = [
        'core' => CoreModule::class,
        'patients' => PatientsModule::class,
        'providers' => ProvidersModule::class,
        'appointments' => AppointmentsModule::class,
        'health_guide' => HealthGuideModule::class,
        'billing' => BillingModule::class,
        'clinical' => ClinicalModule::class,
        'medications' => MedicationsModule::class,
        'wellness' => WellnessModule::class,
        'ai' => AiModule::class,
        'reports' => ReportsModule::class,
        'whatsapp' => WhatsappModule::class,
        'seo' => SeoModule::class,
        'marketing' => MarketingModule::class,
        'crm' => CrmModule::class,
    ];

    /**
     * @return list<HealthAssistModule>
     */
    public function all(): array
    {
        return array_map(fn (string $class) => app($class), array_values($this->definitions));
    }

    public function get(string $key): HealthAssistModule
    {
        if (! isset($this->definitions[$key])) {
            throw new InvalidArgumentException("Unknown module [{$key}].");
        }

        return app($this->definitions[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions);
    }

    /**
     * Map experience nav keys to owning platform module keys.
     *
     * @return array<string, list<string>>
     */
    public function navigationToModules(): array
    {
        $map = [];
        foreach ($this->all() as $module) {
            foreach ($module->navigation() as $navKey) {
                $map[$navKey] ??= [];
                if (! in_array($module->key(), $map[$navKey], true)) {
                    $map[$navKey][] = $module->key();
                }
            }
        }

        return $map;
    }
}
