<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $account_type
 * @property string|null $professional_type
 * @property string|null $active_workspace
 * @property array<string, mixed>|null $meta
 */
#[Fillable(['user_id', 'account_type', 'professional_type', 'active_workspace', 'meta'])]
class MobileUserPreference extends Model
{
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
