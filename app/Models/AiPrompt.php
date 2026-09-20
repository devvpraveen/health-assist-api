<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 */
#[Fillable(['key', 'name', 'description'])]
class AiPrompt extends Model
{
    /**
     * @return HasMany<AiPromptVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AiPromptVersion::class, 'prompt_id');
    }

    /**
     * @return HasOne<AiPromptVersion, $this>
     */
    public function activeVersionRelation(): HasOne
    {
        return $this->hasOne(AiPromptVersion::class, 'prompt_id')
            ->where('status', AiPromptVersion::STATUS_ACTIVE);
    }

    public function activeVersion(): ?AiPromptVersion
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions->firstWhere('status', AiPromptVersion::STATUS_ACTIVE);
        }

        return $this->activeVersionRelation;
    }
}
