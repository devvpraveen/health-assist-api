<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $key
 * @property string $body
 * @property string $language
 * @property bool $is_active
 */
#[Fillable([
    'tenant_id',
    'key',
    'body',
    'language',
    'is_active',
])]
class WhatsAppTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_templates';

    protected $attributes = [
        'language' => 'en',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
