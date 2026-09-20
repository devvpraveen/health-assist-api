<?php

namespace App\Actions\Providers;

use App\Models\Provider;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateProviderAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Provider $provider, array $data): Provider
    {
        return DB::transaction(function () use ($provider, $data): Provider {
            $provider->update($data);

            $this->auditLogger->log('provider.updated', $provider, [
                'provider_uuid' => $provider->uuid,
                'fields' => array_keys($data),
            ]);

            return $provider->refresh();
        });
    }
}
