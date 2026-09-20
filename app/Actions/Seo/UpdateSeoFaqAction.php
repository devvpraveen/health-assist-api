<?php

namespace App\Actions\Seo;

use App\Models\SeoFaq;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateSeoFaqAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(SeoFaq $faq, array $data): SeoFaq
    {
        return DB::transaction(function () use ($faq, $data): SeoFaq {
            $faq->fill($data);
            $faq->save();

            $this->auditLogger->log('seo.faq.updated', $faq, [
                'faq_uuid' => $faq->uuid,
            ]);

            return $faq->fresh();
        });
    }
}
