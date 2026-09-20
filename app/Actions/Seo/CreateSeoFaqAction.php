<?php

namespace App\Actions\Seo;

use App\Models\SeoFaq;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateSeoFaqAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): SeoFaq
    {
        return DB::transaction(function () use ($data): SeoFaq {
            $tenantId = array_key_exists('tenant_id', $data)
                ? $data['tenant_id']
                : TenantContext::id();

            $faq = SeoFaq::query()->create([
                'tenant_id' => $tenantId,
                'entity_id' => $data['entity_id'] ?? null,
                'question' => $data['question'],
                'answer' => $data['answer'],
                'locale' => $data['locale'] ?? 'en',
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? SeoFaq::STATUS_DRAFT,
            ]);

            $this->auditLogger->log('seo.faq.created', $faq, [
                'faq_uuid' => $faq->uuid,
            ]);

            return $faq;
        });
    }
}
