<?php

namespace App\Actions\Seo;

use App\Models\SeoEntity;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishSeoEntityAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(SeoEntity $entity): SeoEntity
    {
        return DB::transaction(function () use ($entity): SeoEntity {
            $userId = Auth::id();

            if ($userId === null) {
                throw ValidationException::withMessages([
                    'reviewer' => 'Authentication required to publish.',
                ]);
            }

            if (! $entity->isReviewed()) {
                $entity->reviewer_user_id = $userId;
                $entity->last_reviewed_at = now();
            }

            if (! $entity->isReviewed()) {
                throw ValidationException::withMessages([
                    'reviewer_user_id' => 'Publishing requires reviewer_user_id and last_reviewed_at.',
                    'last_reviewed_at' => 'Publishing requires reviewer_user_id and last_reviewed_at.',
                ]);
            }

            $entity->status = SeoEntity::STATUS_PUBLISHED;
            $entity->published_at = $entity->published_at ?? now();
            $entity->save();

            $this->auditLogger->log('seo.entity.published', $entity, [
                'entity_uuid' => $entity->uuid,
                'reviewer_user_id' => $entity->reviewer_user_id,
            ]);

            return $entity->fresh(['reviewer', 'author']);
        });
    }
}
