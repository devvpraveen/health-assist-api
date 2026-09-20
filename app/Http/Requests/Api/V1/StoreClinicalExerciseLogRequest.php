<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalExerciseLog;
use App\Models\ClinicalExercisePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalExerciseLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalExercisePlan $exercisePlan */
        $exercisePlan = $this->route('exercise_plan');

        return $this->user()?->can('create', [ClinicalExerciseLog::class, $exercisePlan]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'performed_at' => ['required', 'date'],
            'result' => ['required', 'string', Rule::in(ClinicalExerciseLog::RESULTS)],
            'notes' => ['nullable', 'string'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
        ];
    }
}
