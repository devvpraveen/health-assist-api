<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WhatsAppAccount
 */
class WhatsAppAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'clinic_id' => $this->clinic_id,
            'name' => $this->name,
            'instance_name' => $this->instance_name,
            'phone_number' => $this->phone_number,
            'status' => $this->status,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
