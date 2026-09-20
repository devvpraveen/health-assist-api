<?php

namespace App\Http\Requests\Api\V1\Medications;

use App\Models\MedicationSchedule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MedicationSchedule $schedule */
        $schedule = $this->route('schedule');

        return $this->user()?->can('update', $schedule) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $time = $this->input('time_of_day');

        if (is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            $this->merge(['time_of_day' => $time.':00']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'time_of_day' => ['sometimes', 'date_format:H:i:s'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'days_of_week' => ['sometimes', 'nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
