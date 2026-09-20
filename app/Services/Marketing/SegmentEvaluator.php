<?php

namespace App\Services\Marketing;

use App\Models\Appointment;
use App\Models\AudienceSegment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Evaluates non-clinical audience segment rules only.
 * Never uses symptoms, diagnoses, medications, reports, or chat content.
 */
class SegmentEvaluator
{
    /**
     * @return list<int>
     */
    public function memberIds(AudienceSegment $segment): array
    {
        return $this->members($segment)->all();
    }

    /**
     * @return Collection<int, int>
     */
    public function members(AudienceSegment $segment): Collection
    {
        $definition = $segment->definition ?? [];
        $tenantId = $segment->tenant_id;

        $query = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (isset($definition['inactive_days']) && is_numeric($definition['inactive_days'])) {
            $days = (int) $definition['inactive_days'];
            $cutoff = now()->subDays($days);
            $query->where(function ($builder) use ($cutoff): void {
                $builder->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<', $cutoff);
            });
        }

        if (! empty($definition['new_user'])) {
            $days = is_numeric($definition['new_user_days'] ?? null)
                ? (int) $definition['new_user_days']
                : 7;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        if (! empty($definition['appointment_upcoming'])) {
            $patientUserIds = Patient::query()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('user_id')
                ->whereIn('id', function ($sub) use ($tenantId): void {
                    $sub->select('patient_id')
                        ->from('appointments')
                        ->where('tenant_id', $tenantId)
                        ->where('starts_at', '>', now())
                        ->whereIn('status', [
                            Appointment::STATUS_REQUESTED,
                            Appointment::STATUS_CONFIRMED,
                            Appointment::STATUS_CHECKED_IN,
                        ]);
                })
                ->pluck('user_id');

            $query->whereIn('id', $patientUserIds);
        }

        return $query->orderBy('id')->pluck('id');
    }

    public function count(AudienceSegment $segment): int
    {
        return $this->members($segment)->count();
    }
}
