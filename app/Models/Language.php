<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $native_name
 * @property bool $is_rtl
 * @property bool $is_enabled
 * @property int $sort_order
 */
#[Fillable([
    'code',
    'name',
    'native_name',
    'is_rtl',
    'is_enabled',
    'sort_order',
])]
class Language extends Model
{
    protected $attributes = [
        'is_rtl' => false,
        'is_enabled' => false,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_rtl' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<LanguageScopeAssignment, $this>
     */
    public function scopeAssignments(): HasMany
    {
        return $this->hasMany(LanguageScopeAssignment::class);
    }
}
