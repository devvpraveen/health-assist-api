<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $category
 * @property string $severity
 * @property string $pattern_type
 * @property string $pattern
 * @property string $action
 * @property string $message_template
 * @property int $version
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code',
    'name',
    'category',
    'severity',
    'pattern_type',
    'pattern',
    'action',
    'message_template',
    'version',
    'is_active',
    'sort_order',
])]
class SafetyRule extends Model
{
    public const SEVERITY_EMERGENCY = 'emergency';

    public const SEVERITY_URGENT = 'urgent';

    public const SEVERITY_CLINICIAN_REVIEW = 'clinician_review';

    public const SEVERITY_ROUTINE = 'routine';

    public const SEVERITIES = [
        self::SEVERITY_EMERGENCY,
        self::SEVERITY_URGENT,
        self::SEVERITY_CLINICIAN_REVIEW,
        self::SEVERITY_ROUTINE,
    ];

    public const PATTERN_KEYWORD = 'keyword';

    public const PATTERN_REGEX = 'regex';

    public const ACTION_ESCALATE_EMERGENCY = 'escalate_emergency';

    public const ACTION_ESCALATE_URGENT = 'escalate_urgent';

    public const ACTION_REQUIRE_CLINICIAN_REVIEW = 'require_clinician_review';

    public const ACTION_CONTINUE = 'continue';

    /**
     * Lower index = higher priority when selecting the winning rule.
     *
     * @var array<string, int>
     */
    public const SEVERITY_PRIORITY = [
        self::SEVERITY_EMERGENCY => 0,
        self::SEVERITY_URGENT => 1,
        self::SEVERITY_CLINICIAN_REVIEW => 2,
        self::SEVERITY_ROUTINE => 3,
    ];

    protected $attributes = [
        'version' => 1,
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
