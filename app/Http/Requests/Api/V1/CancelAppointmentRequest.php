<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        return $this->user()?->can('cancel', $appointment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
