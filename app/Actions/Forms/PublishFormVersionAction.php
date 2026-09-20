<?php

namespace App\Actions\Forms;

use App\Models\FormDefinition;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishFormVersionAction
{
    public function handle(FormDefinition $form, FormVersion $version, ?User $actor = null): FormVersion
    {
        abort_unless($version->form_definition_id === $form->id, 404);

        return DB::transaction(function () use ($form, $version, $actor): FormVersion {
            FormVersion::query()
                ->where('form_definition_id', $form->id)
                ->where('status', FormVersion::STATUS_PUBLISHED)
                ->where('id', '!=', $version->id)
                ->update(['status' => FormVersion::STATUS_ARCHIVED]);

            $version->update([
                'status' => FormVersion::STATUS_PUBLISHED,
                'published_at' => now(),
                'created_by_user_id' => $version->created_by_user_id ?? $actor?->id,
            ]);

            $form->update([
                'status' => FormDefinition::STATUS_PUBLISHED,
                'active_version_id' => $version->id,
            ]);

            return $version->fresh();
        });
    }
}
