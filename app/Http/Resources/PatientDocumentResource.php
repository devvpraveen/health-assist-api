<?php

namespace App\Http\Resources;

use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PatientDocument
 */
class PatientDocumentResource extends JsonResource
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
            'health_record_id' => $this->health_record_id,
            'category' => $this->category,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'visibility' => $this->visibility,
            'uploaded_by' => $this->uploaded_by,
            'integrity_hash' => $this->integrity_hash,
            'integrity_status' => $this->integrity_status,
            'integrity_provider' => $this->integrity_provider,
            'integrity_proof_ref' => $this->integrity_proof_ref,
            'integrity_attested_at' => $this->integrity_attested_at,
            'download_url' => url(sprintf(
                '/api/v1/patients/%d/documents/%d/download',
                $this->patient_id,
                $this->id,
            )),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
