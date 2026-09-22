<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Models\User;
use App\Support\TenantContext;

class EnsureLinkedPatientAction
{
    public function handle(User $user): Patient
    {
        $existing = Patient::query()->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $tenantId = $user->tenant_id ?? TenantContext::id();
        if ($tenantId === null) {
            throw new \RuntimeException('Cannot create patient without a tenant.');
        }

        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $first = ($parts[0] ?? '') !== '' ? $parts[0] : 'Patient';
        $last = ($parts[1] ?? '') !== '' ? $parts[1] : 'User';

        return Patient::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'first_name' => $first,
            'last_name' => $last,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => 'active',
            'consent_status' => 'pending',
        ]);
    }
}
