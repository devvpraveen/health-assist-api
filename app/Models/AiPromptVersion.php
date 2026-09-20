<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $prompt_id
 * @property int $version
 * @property string $system_prompt
 * @property string|null $template
 * @property string $status
 * @property int|null $created_by
 * @property Carbon|null $activated_at
 */
#[Fillable([
    'prompt_id',
    'version',
    'system_prompt',
    'template',
    'status',
    'created_by',
    'activated_at',
])]
class AiPromptVersion extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $casts = [
        'activated_at' => 'datetime',
        'version' => 'integer',
    ];

    /**
     * @return BelongsTo<AiPrompt, $this>
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'prompt_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
