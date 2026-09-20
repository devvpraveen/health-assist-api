<?php

namespace App\Modules\Definitions;

class BillingModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'billing';
    }

    public function definition(): array
    {
        return [
            'name' => 'Billing',
            'description' => 'Invoices, payments, packages, and refunds.',
            'category' => 'operations',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients'];
    }

    public function capabilities(): array
    {
        return ['billing.invoices.view', 'billing.payments.view'];
    }

    public function permissions(): array
    {
        return [
            'billing.invoices.view',
            'billing.invoices.manage',
            'billing.payments.view',
            'billing.payments.manage',
            'billing.packages.view',
            'billing.packages.manage',
            'billing.refunds.manage',
        ];
    }

    public function navigation(): array
    {
        return ['billing', 'dashboard'];
    }
}
