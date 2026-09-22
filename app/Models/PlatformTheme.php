<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $channel
 * @property int $version
 * @property array<string, mixed> $config
 * @property int|null $updated_by
 * @property Carbon|null $published_at
 */
#[Fillable(['channel', 'version', 'config', 'updated_by', 'published_at'])]
class PlatformTheme extends Model
{
    public const CHANNEL_DRAFT = 'draft';

    public const CHANNEL_PUBLISHED = 'published';

    protected $casts = [
        'config' => 'array',
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
