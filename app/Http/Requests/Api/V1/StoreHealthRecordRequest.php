<?php

namespace App\Http\Requests\Api\V1;

use App\Models\HealthRecord;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [HealthRecord::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(HealthRecord::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'recorded_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(HealthRecord::STATUSES)],
        ];
    }
}
