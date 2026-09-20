<?php

namespace App\Actions\Seo;

use App\Models\SeoEntity;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateSeoEntityAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(SeoEntity $entity, array $data): SeoEntity
    {
        return DB::transaction(function () use ($entity, $data): SeoEntity {
            $this->assertTenantAccess($entity);

            if (
                array_key_exists('status', $data)
                && $data['status'] === SeoEntity::STATUS_PUBLISHED
                && $entity->status !== SeoEntity::STATUS_PUBLISHED
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Use the publish endpoint to publish SEO entities after review.',
                ]);
            }

            if (isset($data['title']) && ! isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if (array_key_exists('citations', $data) && $data['citations'] === null) {
                $data['citations'] = [];
            }

            $entity->fill($data);
            $entity->save();

            $this->auditLogger->log('seo.entity.updated', $entity, [
                'entity_uuid' => $entity->uuid,
                'status' => $entity->status,
            ]);

            return $entity->fresh();
        });
    }

    private function assertTenantAccess(SeoEntity $entity): void
    {
        $tenantId = TenantContext::id();

        if ($entity->tenant_id === null) {
            return;
        }

        if ($tenantId !== null && $entity->tenant_id !== $tenantId) {
            abort(404);
        }
    }
}
