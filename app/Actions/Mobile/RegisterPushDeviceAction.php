<?php

namespace App\Actions\Mobile;

use App\Models\PushDevice;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class RegisterPushDeviceAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array{token: string, platform?: string}  $data
     */
    public function handle(User $user, array $data): PushDevice
    {
        return DB::transaction(function () use ($user, $data): PushDevice {
            $tenantId = TenantContext::id() ?? $user->tenant_id;

            $device = PushDevice::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'token' => $data['token'],
                ],
                [
                    'user_id' => $user->id,
                    'platform' => $data['platform'] ?? 'unknown',
                    'last_seen_at' => now(),
                ],
            );

            $this->auditLogger->log('mobile.push_device.registered', $device, [
                'platform' => $device->platform,
                'user_id' => $user->id,
            ]);

            return $device->fresh() ?? $device;
        });
    }
}
