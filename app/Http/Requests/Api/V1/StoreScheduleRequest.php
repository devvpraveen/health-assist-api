<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Provider;
use App\Models\Schedule;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Schedule::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        /** @var Provider|null $provider */
        $provider = $this->route('provider');

        return [
            'provider_id' => [
                Rule::requiredIf($provider === null),
                'integer',
                Rule::exists('providers', 'id')->where('tenant_id', $tenantId),
            ],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'clinic_id' => [
                'sometimes',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if (is_string($start) && is_string($end) && $end <= $start) {
                $validator->errors()->add('end_time', 'The end time must be after the start time.');
            }
        });
    }
}
