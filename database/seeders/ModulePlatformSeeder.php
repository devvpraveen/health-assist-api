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

        // À-la-carte module pricing (amount + validity). Core is not purchasable alone.
        $modulePricing = [
            'core' => ['price_cents' => 0, 'validity_days' => null, 'is_purchasable' => false],
            'patients' => ['price_cents' => 49900, 'validity_days' => 30, 'is_purchasable' => true],
            'providers' => ['price_cents' => 29900, 'validity_days' => 30, 'is_purchasable' => true],
            'appointments' => ['price_cents' => 69900, 'validity_days' => 30, 'is_purchasable' => true],
            'health_guide' => ['price_cents' => 149900, 'validity_days' => 30, 'is_purchasable' => true],
            'billing' => ['price_cents' => 99900, 'validity_days' => 30, 'is_purchasable' => true],
            'ai' => ['price_cents' => 199900, 'validity_days' => 30, 'is_purchasable' => true],
            'clinical' => ['price_cents' => 79900, 'validity_days' => 30, 'is_purchasable' => true],
            'medications' => ['price_cents' => 59900, 'validity_days' => 30, 'is_purchasable' => true],
            'reports' => ['price_cents' => 49900, 'validity_days' => 30, 'is_purchasable' => true],
            'whatsapp' => ['price_cents' => 129900, 'validity_days' => 30, 'is_purchasable' => true],
            'marketing' => ['price_cents' => 89900, 'validity_days' => 30, 'is_purchasable' => true],
            'crm' => ['price_cents' => 89900, 'validity_days' => 30, 'is_purchasable' => true],
            'wellness' => ['price_cents' => 59900, 'validity_days' => 30, 'is_purchasable' => true],
            'seo' => ['price_cents' => 39900, 'validity_days' => 30, 'is_purchasable' => true],
            'prescriptions' => ['price_cents' => 49900, 'validity_days' => 30, 'is_purchasable' => true],
            'certificates' => ['price_cents' => 39900, 'validity_days' => 30, 'is_purchasable' => true],
            'estimates' => ['price_cents' => 39900, 'validity_days' => 30, 'is_purchasable' => true],
            'staff_hr' => ['price_cents' => 49900, 'validity_days' => 30, 'is_purchasable' => true],
            'notice_board' => ['price_cents' => 19900, 'validity_days' => 30, 'is_purchasable' => true],
            'reviews' => ['price_cents' => 29900, 'validity_days' => 30, 'is_purchasable' => true],
            'media' => ['price_cents' => 29900, 'validity_days' => 30, 'is_purchasable' => true],
            'ai_receptionist' => ['price_cents' => 249900, 'validity_days' => 30, 'is_purchasable' => true],
            'pharmacy' => ['price_cents' => 149900, 'validity_days' => 30, 'is_purchasable' => true],
            'lab' => ['price_cents' => 149900, 'validity_days' => 30, 'is_purchasable' => true],
            'inventory' => ['price_cents' => 99900, 'validity_days' => 30, 'is_purchasable' => true],
        ];

        foreach ($modulePricing as $moduleKey => $pricing) {
            PlatformModule::query()->where('key', $moduleKey)->update([
                'price_cents' => $pricing['price_cents'],
                'currency' => 'INR',
                'validity_days' => $pricing['validity_days'],
                'is_purchasable' => $pricing['is_purchasable'],
            ]);
        }

        // Default any remaining modules to a standard monthly add-on price.
        PlatformModule::query()
            ->whereNotIn('key', array_keys($modulePricing))
            ->where('price_cents', 0)
            ->update([
                'price_cents' => 49900,
                'currency' => 'INR',
                'validity_days' => 30,
                'is_purchasable' => true,
            ]);

        $modules = PlatformModule::query()->get()->keyBy('key');

        $catalog = [
            'free' => [
                'name' => 'Free',
                'description' => 'Patients, providers, and appointments for solo starts — upgrade modules anytime.',
                'sort_order' => 5,
                'price_cents' => 0,
                'validity_days' => null,
                'included' => ['core', 'patients', 'providers', 'appointments'],
                'addon_eligible' => ['health_guide', 'billing', 'ai', 'clinical', 'notice_board', 'reviews', 'media', 'staff_hr'],
                'entitlements' => ['ai_assistant' => false, 'whatsapp' => false, 'custom_branding' => false],
                'limits' => [
                    'patients' => 50,
                    'staff' => 2,
                    'branches' => 1,
                    'ai_messages_month' => 50,
                    'ai_reports_month' => 5,
                    'storage_gb' => 1,
                    'appointments_month' => 100,
                ],
            ],
            'starter' => [
                'name' => 'Starter',
                'description' => 'Growing clinics: staff seats, notice board, and room to add AI or billing later.',
                'sort_order' => 10,
                'price_cents' => 199900,
                'validity_days' => 30,
                'included' => ['core', 'patients', 'providers', 'appointments', 'staff_hr', 'notice_board'],
                'addon_eligible' => ['health_guide', 'billing', 'ai', 'medications', 'clinical', 'reports', 'whatsapp', 'marketing', 'crm', 'wellness', 'seo', 'prescriptions', 'certificates', 'reviews', 'media'],
                'entitlements' => ['ai_assistant' => false, 'whatsapp' => false, 'custom_branding' => false],
                'limits' => [
                    'patients' => 500,
                    'staff' => 5,
                    'branches' => 1,
                    'ai_messages_month' => 1000,
                    'ai_reports_month' => 50,
                    'storage_gb' => 10,
                    'appointments_month' => 2000,
                ],
            ],
            'professional' => [
                'name' => 'Professional',
                'description' => 'Clinical ops with AI Health Guide, billing, medications, reports, and branding.',
                'sort_order' => 20,
                'price_cents' => 499900,
                'validity_days' => 30,
                'included' => ['core', 'patients', 'providers', 'appointments', 'health_guide', 'billing', 'clinical', 'medications', 'reports', 'ai', 'prescriptions', 'certificates', 'estimates', 'staff_hr', 'notice_board', 'reviews', 'media'],
                'addon_eligible' => ['whatsapp', 'marketing', 'crm', 'wellness', 'seo', 'ai_receptionist'],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => false, 'custom_branding' => true],
                'limits' => [
                    'patients' => 2000,
                    'staff' => 10,
                    'branches' => 2,
                    'ai_messages_month' => 10000,
                    'ai_reports_month' => 500,
                    'storage_gb' => 50,
                    'appointments_month' => null,
                ],
            ],
            'clinic' => [
                'name' => 'Clinic',
                'description' => 'Growth stack: WhatsApp, CRM, marketing, and AI receptionist for multi-provider clinics.',
                'sort_order' => 30,
                'price_cents' => 999900,
                'validity_days' => 30,
                'included' => ['core', 'patients', 'providers', 'appointments', 'health_guide', 'billing', 'clinical', 'medications', 'reports', 'ai', 'whatsapp', 'marketing', 'crm', 'wellness', 'seo', 'prescriptions', 'certificates', 'estimates', 'staff_hr', 'notice_board', 'reviews', 'media', 'ai_receptionist'],
                'addon_eligible' => ['pharmacy', 'lab', 'inventory'],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => true, 'custom_branding' => true, 'custom_domain' => false],
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
            'growth' => [
                'name' => 'Growth',
                'description' => 'Alias of Clinic — WhatsApp, CRM, marketing, and AI receptionist for scaling practices.',
                'sort_order' => 31,
                'price_cents' => 999900,
                'validity_days' => 30,
                'included' => ['core', 'patients', 'providers', 'appointments', 'health_guide', 'billing', 'clinical', 'medications', 'reports', 'ai', 'whatsapp', 'marketing', 'crm', 'wellness', 'seo', 'prescriptions', 'certificates', 'estimates', 'staff_hr', 'notice_board', 'reviews', 'media', 'ai_receptionist'],
                'addon_eligible' => ['pharmacy', 'lab', 'inventory'],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => true, 'custom_branding' => true],
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
            'hospital' => [
                'name' => 'Hospital',
                'description' => 'Full stack with pharmacy, lab, inventory, API access, and elevated multi-branch limits.',
                'sort_order' => 35,
                'price_cents' => 2499900,
                'validity_days' => 30,
                'included' => ['core', 'patients', 'providers', 'appointments', 'health_guide', 'billing', 'clinical', 'medications', 'reports', 'ai', 'whatsapp', 'marketing', 'crm', 'wellness', 'seo', 'prescriptions', 'certificates', 'estimates', 'pharmacy', 'lab', 'inventory', 'staff_hr', 'notice_board', 'reviews', 'media', 'ai_receptionist'],
                'addon_eligible' => [],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => true, 'custom_branding' => true, 'custom_domain' => true, 'api_access' => true],
                'limits' => [
                    'patients' => 50000,
                    'staff' => 500,
                    'branches' => 50,
                    'ai_messages_month' => 200000,
                    'ai_reports_month' => 10000,
                    'storage_gb' => 1000,
                    'whatsapp_messages_month' => 50000,
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'All modules, custom limits, and dedicated support for health systems.',
                'sort_order' => 40,
                'price_cents' => 0,
                'validity_days' => 365,
                'included' => $registry->keys(),
                'addon_eligible' => [],
                'entitlements' => ['ai_assistant' => true, 'whatsapp' => true, 'api_access' => true, 'custom_branding' => true, 'custom_domain' => true],
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
                    'price_cents' => $config['price_cents'],
                    'currency' => 'INR',
                    'validity_days' => $config['validity_days'],
                    'metadata' => [
                        'price_monthly' => (int) floor(((int) $config['price_cents']) / 100),
                    ],
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
