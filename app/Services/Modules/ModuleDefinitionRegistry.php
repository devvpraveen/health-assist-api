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
use App\Modules\Definitions\StubCapabilityModule;
use App\Modules\Definitions\WellnessModule;
use App\Modules\Definitions\WhatsappModule;
use InvalidArgumentException;

class ModuleDefinitionRegistry
{
    /** @var array<string, class-string<HealthAssistModule>|callable(): HealthAssistModule> */
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

    public function __construct()
    {
        $stubs = [
            'prescriptions' => ['Prescriptions', 'Create and share prescriptions as PDFs.', 'clinical', ['core', 'patients'], ['prescriptions']],
            'certificates' => ['Certificates', 'Medical, fitness, and custom certificates.', 'clinical', ['core', 'patients'], ['certificates']],
            'estimates' => ['Estimates', 'Treatment estimates and quotes.', 'billing', ['core', 'billing'], ['estimates']],
            'pharmacy' => ['Pharmacy', 'Dispense and inventory for on-site pharmacy.', 'clinical', ['core', 'medications'], ['pharmacy']],
            'lab' => ['Laboratory', 'Lab orders and results.', 'clinical', ['core', 'patients'], ['lab']],
            'inventory' => ['Inventory', 'Clinic inventory and stock.', 'operations', ['core'], ['inventory']],
            'notice_board' => ['Notice Board', 'Clinic notices and announcements.', 'growth', ['core'], ['notice_board']],
            'reviews' => ['Reviews', 'Patient feedback and public reviews.', 'growth', ['core'], ['reviews']],
            'media' => ['Media Manager', 'Photos, videos, and facility media.', 'growth', ['core'], ['media']],
            'staff_hr' => ['Staff', 'Staff profiles, attendance, and roster.', 'operations', ['core', 'providers'], ['staff']],
            'ai_receptionist' => ['AI Receptionist', 'FAQ, booking, and escalation via chat/WhatsApp.', 'ai', ['core', 'ai', 'appointments'], ['ai_receptionist']],
        ];

        foreach ($stubs as $key => [$name, $description, $category, $deps, $nav]) {
            $this->definitions[$key] = fn () => new StubCapabilityModule(
                $key,
                $name,
                $description,
                $category,
                $deps,
                $nav,
            );
        }
    }

    /**
     * @return list<HealthAssistModule>
     */
    public function all(): array
    {
        return array_map(fn (string $key) => $this->get($key), array_keys($this->definitions));
    }

    public function get(string $key): HealthAssistModule
    {
        if (! isset($this->definitions[$key])) {
            throw new InvalidArgumentException("Unknown module [{$key}].");
        }

        $entry = $this->definitions[$key];
        if (is_callable($entry)) {
            return $entry();
        }

        return app($entry);
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
