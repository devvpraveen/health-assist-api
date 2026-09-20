<?php

namespace App\Http\Requests\Api\V1;

use App\Models\HealthRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var HealthRecord $record */
        $record = $this->route('health_record');

        return $this->user()?->can('update', $record) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', Rule::in(HealthRecord::CATEGORIES)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'recorded_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(HealthRecord::STATUSES)],
        ];
    }
}
