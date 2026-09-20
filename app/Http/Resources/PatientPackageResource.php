<?php

namespace App\Http\Resources;

use App\Models\PatientPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PatientPackage
 */
class PatientPackageResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'package_id' => $this->package_id,
            'invoice_id' => $this->invoice_id,
            'payment_id' => $this->payment_id,
            'sessions_total' => $this->sessions_total,
            'sessions_used' => $this->sessions_used,
            'sessions_remaining' => $this->sessions_remaining,
            'purchased_at' => $this->purchased_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'package' => BillingPackageResource::make($this->whenLoaded('package')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
