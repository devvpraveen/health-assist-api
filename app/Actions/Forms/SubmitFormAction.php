<?php

namespace App\Actions\Forms;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\Forms\FormEngine;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitFormAction
{
    public function __construct(private FormEngine $forms) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $meta
     */
    public function handle(
        FormDefinition $form,
        array $payload,
        ?User $actor = null,
        ?Model $subject = null,
        ?array $meta = null,
    ): FormSubmission {
        $version = $form->activeVersion;
        if ($version === null || $form->status !== FormDefinition::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'form' => ['Form is not published.'],
            ]);
        }

        $sanitized = $this->forms->validate($version, $payload);
        $tenantId = (int) (TenantContext::id() ?? $form->tenant_id ?? $actor?->tenant_id);

        return DB::transaction(function () use ($form, $version, $sanitized, $actor, $subject, $meta, $tenantId): FormSubmission {
            return FormSubmission::query()->create([
                'tenant_id' => $tenantId,
                'form_definition_id' => $form->id,
                'form_version_id' => $version->id,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'payload' => $sanitized,
                'meta' => $meta,
                'submitted_by_user_id' => $actor?->id,
            ]);
        });
    }
}
