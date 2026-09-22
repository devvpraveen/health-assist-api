<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string $locale
 * @property string $value
 */
#[Fillable(['group', 'key', 'locale', 'value'])]
class UiTranslation extends Model
{
    public const GROUP_WEBSITE = 'website';

    protected $attributes = [
        'group' => self::GROUP_WEBSITE,
    ];
}
