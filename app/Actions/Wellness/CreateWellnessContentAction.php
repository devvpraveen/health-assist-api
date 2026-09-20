<?php

namespace App\Actions\Wellness;

use App\Models\WellnessContent;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWellnessContentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): WellnessContent
    {
        return DB::transaction(function () use ($data): WellnessContent {
            $tenantId = array_key_exists('tenant_id', $data)
                ? $data['tenant_id']
                : TenantContext::id();

            $status = $data['status'] ?? WellnessContent::STATUS_DRAFT;
            $isClinical = false;
            if (config('wellness.force_non_clinical', true)) {
                $isClinical = false;
            } elseif (array_key_exists('is_clinical_advice', $data)) {
                $isClinical = (bool) $data['is_clinical_advice'];
            }

            $content = WellnessContent::query()->create([
                'tenant_id' => $tenantId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'summary' => $data['summary'],
                'body' => $data['body'],
                'locale' => $data['locale'] ?? 'en',
                'is_clinical_advice' => $isClinical,
                'status' => $status,
                'author_user_id' => Auth::id(),
                'published_at' => $status === WellnessContent::STATUS_PUBLISHED
                    ? ($data['published_at'] ?? now())
                    : null,
                'personalization_tags' => $data['personalization_tags'] ?? null,
            ]);

            $this->auditLogger->log('wellness.content.created', $content, [
                'content_uuid' => $content->uuid,
                'status' => $content->status,
            ]);

            return $content->load('category');
        });
    }
}
