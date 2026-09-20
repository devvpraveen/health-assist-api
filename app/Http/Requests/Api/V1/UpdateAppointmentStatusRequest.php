<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        return $this->user()?->can('updateStatus', $appointment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                Appointment::STATUS_REQUESTED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
                Appointment::STATUS_IN_CONSULTATION,
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_NO_SHOW,
                Appointment::STATUS_CANCELLED,
            ])],
        ];
    }
}
