<?php

namespace App\Http\Resources;

use App\Models\SafetyRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SafetyRule
 */
class SafetyRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'severity' => $this->severity,
            'pattern_type' => $this->pattern_type,
            'pattern' => $this->pattern,
            'action' => $this->action,
            'message_template' => $this->message_template,
            'version' => $this->version,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
