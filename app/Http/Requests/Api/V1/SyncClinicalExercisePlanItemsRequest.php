<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalExercisePlan;
use Illuminate\Foundation\Http\FormRequest;

class SyncClinicalExercisePlanItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalExercisePlan $exercisePlan */
        $exercisePlan = $this->route('exercise_plan');

        return $this->user()?->can('update', $exercisePlan) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.exercise_id' => ['nullable', 'integer'],
            'items.*.custom_name' => ['nullable', 'string', 'max:255'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.sets' => ['nullable', 'integer', 'min:1'],
            'items.*.reps' => ['nullable', 'integer', 'min:1'],
            'items.*.duration_seconds' => ['nullable', 'integer', 'min:1'],
            'items.*.instructions' => ['nullable', 'string'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
