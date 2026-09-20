<?php

namespace App\Actions\Appointments;

use App\Models\AppointmentWaitlistEntry;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelWaitlistEntryAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(AppointmentWaitlistEntry $entry): AppointmentWaitlistEntry
    {
        return DB::transaction(function () use ($entry): AppointmentWaitlistEntry {
            $entry = AppointmentWaitlistEntry::query()->lockForUpdate()->findOrFail($entry->id);

            if ($entry->status === AppointmentWaitlistEntry::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Waitlist entry is already cancelled.'],
                ]);
            }

            $entry->update(['status' => AppointmentWaitlistEntry::STATUS_CANCELLED]);

            $this->auditLogger->log('appointment_waitlist.cancelled', $entry, [
                'waitlist_uuid' => $entry->uuid,
            ]);

            return $entry->fresh(['patient', 'clinic', 'provider']);
        });
    }
}
