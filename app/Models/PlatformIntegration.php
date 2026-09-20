<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $category
 * @property string $status
 * @property string|null $driver
 * @property array<string, mixed>|null $config
 */
#[Fillable(['key', 'name', 'category', 'status', 'driver', 'config'])]
class PlatformIntegration extends Model
{
    public const STATUSES = ['connected', 'standby', 'error', 'disabled'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
