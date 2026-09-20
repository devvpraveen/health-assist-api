<?php

namespace App\Actions\AI;

use App\Models\AiPromptVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivatePromptVersionAction
{
    public function handle(AiPromptVersion $version): AiPromptVersion
    {
        return DB::transaction(function () use ($version): AiPromptVersion {
            $version = AiPromptVersion::query()->lockForUpdate()->findOrFail($version->id);

            if ($version->status === AiPromptVersion::STATUS_ARCHIVED) {
                throw ValidationException::withMessages([
                    'version' => ['Archived prompt versions cannot be activated.'],
                ]);
            }

            AiPromptVersion::query()
                ->where('prompt_id', $version->prompt_id)
                ->where('id', '!=', $version->id)
                ->where('status', AiPromptVersion::STATUS_ACTIVE)
                ->update([
                    'status' => AiPromptVersion::STATUS_ARCHIVED,
                    'updated_at' => now(),
                ]);

            $version->forceFill([
                'status' => AiPromptVersion::STATUS_ACTIVE,
                'activated_at' => now(),
            ])->save();

            return $version->fresh();
        });
    }
}
