<?php

namespace App\Actions\Mobile;

use App\Models\PushDevice;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UnregisterPushDeviceAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(PushDevice $device): void
    {
        DB::transaction(function () use ($device): void {
            $this->auditLogger->log('mobile.push_device.unregistered', $device, [
                'platform' => $device->platform,
                'user_id' => $device->user_id,
            ]);

            $device->delete();
        });
    }
}
