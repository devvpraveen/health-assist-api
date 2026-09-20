<?php

namespace App\Services\WhatsApp;

use App\Models\Patient;
use Illuminate\Support\Collection;

class PatientPhoneMatcher
{
    public function findForTenant(int $tenantId, string $remotePhoneDigits): ?Patient
    {
        $digits = PhoneNormalizer::digits($remotePhoneDigits);
        if ($digits === '') {
            return null;
        }

        /** @var Collection<int, Patient> $candidates */
        $candidates = Patient::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get(['id', 'tenant_id', 'phone']);

        foreach ($candidates as $patient) {
            if (PhoneNormalizer::matches($patient->phone, $digits)) {
                return Patient::query()->withoutGlobalScopes()->find($patient->id);
            }
        }

        return null;
    }
}
