<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $value
 * @property string $group
 */
#[Fillable(['key', 'label', 'value', 'group'])]
class PlatformSetting extends Model
{
}
