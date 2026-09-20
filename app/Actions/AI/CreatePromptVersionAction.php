<?php

namespace App\Actions\AI;

use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Support\Facades\DB;

class CreatePromptVersionAction
{
    /**
     * @param  array{system_prompt: string, template?: string|null, status?: string}  $data
     */
    public function handle(AiPrompt $prompt, array $data, ?int $userId = null): AiPromptVersion
    {
        return DB::transaction(function () use ($prompt, $data, $userId): AiPromptVersion {
            $nextVersion = ((int) $prompt->versions()->max('version')) + 1;
            $status = $data['status'] ?? AiPromptVersion::STATUS_DRAFT;

            $version = AiPromptVersion::query()->create([
                'prompt_id' => $prompt->id,
                'version' => $nextVersion,
                'system_prompt' => $data['system_prompt'],
                'template' => $data['template'] ?? null,
                'status' => $status === AiPromptVersion::STATUS_ACTIVE
                    ? AiPromptVersion::STATUS_DRAFT
                    : $status,
                'created_by' => $userId,
            ]);

            if ($status === AiPromptVersion::STATUS_ACTIVE) {
                return app(ActivatePromptVersionAction::class)->handle($version);
            }

            return $version->fresh();
        });
    }
}
