<?php

namespace App\Actions\Seo;

use App\Models\SeoEntity;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSeoEntityAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): SeoEntity
    {
        return DB::transaction(function () use ($data): SeoEntity {
            $tenantId = array_key_exists('tenant_id', $data)
                ? $data['tenant_id']
                : TenantContext::id();

            $status = $data['status'] ?? SeoEntity::STATUS_DRAFT;

            if ($status === SeoEntity::STATUS_PUBLISHED) {
                throw ValidationException::withMessages([
                    'status' => 'Use the publish endpoint to publish SEO entities after review.',
                ]);
            }

            $entity = SeoEntity::query()->create([
                'tenant_id' => $tenantId,
                'type' => $data['type'],
                'title' => $data['title'],
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'summary' => $data['summary'],
                'body' => $data['body'] ?? null,
                'locale' => $data['locale'] ?? 'en',
                'parent_entity_id' => $data['parent_entity_id'] ?? null,
                'status' => $status,
                'author_user_id' => Auth::id(),
                'reviewer_user_id' => $data['reviewer_user_id'] ?? null,
                'last_reviewed_at' => $data['last_reviewed_at'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'canonical_path' => $data['canonical_path'] ?? null,
                'schema_type' => $data['schema_type'] ?? null,
                'structured_facts' => $data['structured_facts'] ?? [],
                'direct_answer' => $data['direct_answer'] ?? null,
                'citations' => $data['citations'] ?? [],
                'published_at' => null,
            ]);

            $this->auditLogger->log('seo.entity.created', $entity, [
                'entity_uuid' => $entity->uuid,
                'type' => $entity->type,
                'status' => $entity->status,
            ]);

            return $entity;
        });
    }
}
