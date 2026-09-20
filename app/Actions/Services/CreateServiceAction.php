<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateServiceAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Service
    {
        return DB::transaction(function () use ($data): Service {
            if (empty($data['slug']) && ! empty($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $service = Service::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id(),
            ]);

            $this->auditLogger->log('service.created', $service, [
                'service_uuid' => $service->uuid,
                'clinic_id' => $service->clinic_id,
            ]);

            return $service;
        });
    }
}
