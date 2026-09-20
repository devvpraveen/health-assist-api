<?php

namespace Database\Seeders;

use App\Actions\Modules\AssignPackageToTenantAction;
use App\Models\Package;
use App\Models\PackageEntitlement;
use App\Models\PackageLimit;
use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Modules\Definitions\AbstractModuleDefinition;
use App\Services\Modules\ModuleDefinitionRegistry;
use Illuminate\Database\Seeder;

class ModulePlatformSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(ModuleDefinitionRegistry::class);

        foreach ($registry->all() as $definition) {
            /** @var AbstractModuleDefinition $definition */
            $row = $definition->toRegistryRow();
            PlatformModule::query()->updateOrCreate(
                ['key' => $row['key']],
                $row,
            );
        }

        $modules = PlatformModule::query()->get()->keyBy('key');

        $catalog = [
            'starter' => [
                'name' => 'Starter',
                'description' => 'Patients, appointments, and basic clinic operations.',
                'sort_order' => 10,
                'included' => ['core', 'patients', 'providers', 'appointments'],
                'addon_eligible' => ['health_guide', 'billing', 'ai', 'medications', 'clinical', 'reports', 'whatsapp', 'marketing', 'crm', 'wellness', 'seo'],
                'entitlements' => ['ai_assistant' => false, 'whatsapp' => false],
                'limits' => [
                    'patients' => 500,
                    'staff' => 5,
                    'branches' => 1,
                    'ai_messages_month' => 1000,
                    'ai_reports_month' => 50,
                    'storage_gb' => 10,
                ],
            ],
            'professional' => [
                'name' => 'Professional',
                'description' => 'Clinical ops plus AI Health Guide, billing, and reports.',
                'sort_order' => 20,
                'included' => ['core', 'patients', 'providers', 'appointments', 'health_guide', 'billing', 'clinical', 'medications', 'reports', 'ai'],
                'addon_eligible' => ['whatsapp', 'marketing', 'crm', 'wellness', 'seo'],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => false],
                'limits' => [
                    'patients' => 2000,
                    'staff' => 10,
                    'branches' => 2,
                    'ai_messages_month' => 10000,
                    'ai_reports_month' => 500,
                    'storage_gb' => 50,
                ],
            ],
            'growth' => [
                'name' => 'Growth',
                'description' => 'Professional plus WhatsApp, CRM, marketing, and wellness.',
                'sort_order' => 30,
                'included' => ['core', 'patients', 'providers', 'appointments', 'health_guide', 'billing', 'clinical', 'medications', 'reports', 'ai', 'whatsapp', 'marketing', 'crm', 'wellness', 'seo'],
                'addon_eligible' => [],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => true],
                'limits' => [
                    'patients' => 10000,
                    'staff' => 50,
                    'branches' => 10,
                    'ai_messages_month' => 50000,
                    'ai_reports_month' => 2000,
                    'storage_gb' => 200,
                    'whatsapp_messages_month' => 5000,
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'All modules with elevated limits.',
                'sort_order' => 40,
                'included' => $registry->keys(),
                'addon_eligible' => [],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => true, 'api_access' => true],
                'limits' => [
                    'patients' => null,
                    'staff' => null,
                    'branches' => null,
                    'ai_messages_month' => null,
                    'ai_reports_month' => null,
                    'storage_gb' => null,
                    'whatsapp_messages_month' => null,
                ],
            ],
        ];

        foreach ($catalog as $key => $config) {
            $package = Package::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $config['name'],
                    'slug' => $key,
                    'description' => $config['description'],
                    'sort_order' => $config['sort_order'],
                    'is_active' => true,
                ],
            );

            $sync = [];
            foreach ($config['included'] as $moduleKey) {
                if (! isset($modules[$moduleKey])) {
                    continue;
                }
                $sync[$modules[$moduleKey]->id] = ['inclusion' => 'included'];
            }
            foreach ($config['addon_eligible'] as $moduleKey) {
                if (! isset($modules[$moduleKey]) || isset($sync[$modules[$moduleKey]->id])) {
                    continue;
                }
                $sync[$modules[$moduleKey]->id] = ['inclusion' => 'addon_eligible'];
            }
            $package->modules()->sync($sync);

            foreach ($config['entitlements'] as $entKey => $enabled) {
                PackageEntitlement::query()->updateOrCreate(
                    ['package_id' => $package->id, 'key' => $entKey],
                    ['enabled' => (bool) $enabled],
                );
            }

            foreach ($config['limits'] as $limitKey => $value) {
                PackageLimit::query()->updateOrCreate(
                    ['package_id' => $package->id, 'key' => $limitKey],
                    ['value' => $value, 'period' => 'month'],
                );
            }
        }

        $demo = Tenant::query()->where('slug', 'healthassist-demo')->first();
        $professional = Package::query()->where('key', 'professional')->first();
        if ($demo && $professional) {
            app(AssignPackageToTenantAction::class)->handle($demo, $professional, true);
        }
    }
}
