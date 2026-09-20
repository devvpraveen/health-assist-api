<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Schedule;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Schedule $schedule */
        $schedule = $this->route('schedule');

        return $this->user()?->can('update', $schedule) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'day_of_week' => ['sometimes', 'integer', 'between:0,6'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'slot_duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Schedule $schedule */
            $schedule = $this->route('schedule');
            $start = $this->input('start_time', substr((string) $schedule->start_time, 0, 5));
            $end = $this->input('end_time', substr((string) $schedule->end_time, 0, 5));

            if (is_string($start) && is_string($end) && $end <= $start) {
                $validator->errors()->add('end_time', 'The end time must be after the start time.');
            }
        });
    }
}
