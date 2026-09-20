<?php

namespace App\Http\Requests\Api\V1\Marketing;

use App\Models\EmailWorkflow;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('subscribe', EmailWorkflow::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'consented' => ['required', 'accepted'],
        ];
    }
}
