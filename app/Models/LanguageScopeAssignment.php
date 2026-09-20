<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $language_id
 * @property string $scope
 * @property bool $is_enabled
 */
#[Fillable([
    'language_id',
    'scope',
    'is_enabled',
])]
class LanguageScopeAssignment extends Model
{
    protected $attributes = [
        'is_enabled' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
