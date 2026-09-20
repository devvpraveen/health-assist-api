<?php

namespace App\Http\Resources;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Language
 */
class LanguageResource extends JsonResource
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
            'native_name' => $this->native_name,
            'is_rtl' => $this->is_rtl,
            'is_enabled' => $this->is_enabled,
            'sort_order' => $this->sort_order,
            'scopes' => $this->whenLoaded('scopeAssignments', fn () => $this->scopeAssignments->map(fn ($assignment) => [
                'scope' => $assignment->scope,
                'is_enabled' => $assignment->is_enabled,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
