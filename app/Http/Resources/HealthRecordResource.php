<?php

namespace App\Http\Resources;

use App\Models\HealthRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HealthRecord
 */
class HealthRecordResource extends JsonResource
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
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'recorded_at' => $this->recorded_at,
            'status' => $this->status,
            'integrity_hash' => $this->integrity_hash,
            'integrity_status' => $this->integrity_status,
            'integrity_provider' => $this->integrity_provider,
            'integrity_proof_ref' => $this->integrity_proof_ref,
            'integrity_attested_at' => $this->integrity_attested_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
