<?php

namespace App\Http\Requests\Api\V1\Medications;

use App\Models\Medication;
use App\Models\MedicationLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Medication $medication */
        $medication = $this->route('medication');

        return $this->user()?->can('log', $medication) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Medication $medication */
        $medication = $this->route('medication');

        return [
            'status' => ['required', 'string', Rule::in(MedicationLog::STATUSES)],
            'scheduled_for' => ['nullable', 'date'],
            'schedule_id' => [
                'nullable',
                'integer',
                Rule::exists('medication_schedules', 'id')->where('medication_id', $medication->id),
            ],
            'notes' => ['nullable', 'string'],
            'logged_at' => ['nullable', 'date'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
