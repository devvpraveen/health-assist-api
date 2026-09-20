<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateServiceAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data): Service {
            $service->update($data);

            $this->auditLogger->log('service.updated', $service, [
                'service_uuid' => $service->uuid,
                'fields' => array_keys($data),
            ]);

            return $service->refresh();
        });
    }
}
