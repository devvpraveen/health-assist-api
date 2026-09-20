<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalProgressNote;
use App\Services\AuditLogger;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Support\Facades\DB;

class UpdateClinicalProgressNoteAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalProgressNote $note, array $data): ClinicalProgressNote
    {
        return DB::transaction(function () use ($note, $data): ClinicalProgressNote {
            ClinicalDocumentWorkflow::assertEditable($note);

            $note->update(collect($data)->only([
                'provider_id',
                'treatment_plan_id',
                'appointment_id',
                'noted_at',
                'note',
                'measurements',
                'source',
            ])->all());

            $this->auditLogger->log('clinical.progress_note.updated', $note, [
                'progress_note_uuid' => $note->uuid,
            ]);

            return $note->fresh();
        });
    }
}
