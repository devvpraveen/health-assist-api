<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEvalRun extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'status',
        'overall_score',
        'groundedness',
        'safety',
        'helpfulness',
        'summary',
    ];

    protected $casts = [
        'overall_score' => 'float',
        'groundedness' => 'float',
        'safety' => 'float',
        'helpfulness' => 'float',
        'summary' => 'array',
    ];
}
