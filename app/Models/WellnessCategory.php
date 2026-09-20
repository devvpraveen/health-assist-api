<?php

namespace App\Models;

use Database\Factories\WellnessCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 */
#[Fillable([
    'slug',
    'name',
    'description',
    'sort_order',
])]
class WellnessCategory extends Model
{
    /** @use HasFactory<WellnessCategoryFactory> */
    use HasFactory;

    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * @return HasMany<WellnessContent, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(WellnessContent::class, 'category_id');
    }
}
