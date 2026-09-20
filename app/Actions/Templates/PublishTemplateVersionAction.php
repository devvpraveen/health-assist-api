<?php

namespace App\Actions\Templates;

use App\Models\ContentTemplate;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishTemplateVersionAction
{
    public function handle(ContentTemplate $template, TemplateVersion $version, ?User $actor = null): TemplateVersion
    {
        abort_unless($version->template_id === $template->id, 404);

        return DB::transaction(function () use ($template, $version, $actor): TemplateVersion {
            TemplateVersion::query()
                ->where('template_id', $template->id)
                ->where('status', TemplateVersion::STATUS_PUBLISHED)
                ->where('id', '!=', $version->id)
                ->update(['status' => TemplateVersion::STATUS_ARCHIVED]);

            $version->update([
                'status' => TemplateVersion::STATUS_PUBLISHED,
                'published_at' => now(),
                'created_by_user_id' => $version->created_by_user_id ?? $actor?->id,
            ]);

            $template->update([
                'status' => ContentTemplate::STATUS_PUBLISHED,
                'active_version_id' => $version->id,
            ]);

            return $version->fresh();
        });
    }
}
