<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalSoapNote;
use App\Services\AuditLogger;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Support\Facades\DB;

class UpdateClinicalSoapNoteAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalSoapNote $note, array $data): ClinicalSoapNote
    {
        return DB::transaction(function () use ($note, $data): ClinicalSoapNote {
            ClinicalDocumentWorkflow::assertEditable($note);

            $note->update(collect($data)->only([
                'provider_id',
                'clinic_id',
                'appointment_id',
                'assessment_id',
                'subjective',
                'objective',
                'assessment',
                'plan',
                'session_date',
                'source',
            ])->all());

            $this->auditLogger->log('clinical.soap_note.updated', $note, [
                'soap_note_uuid' => $note->uuid,
            ]);

            return $note->fresh();
        });
    }
}
