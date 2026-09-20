<?php

namespace App\Http\Requests\Api\V1\Mobile;

use App\Models\PushDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePushDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PushDevice::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:8', 'max:512'],
            'platform' => ['sometimes', 'string', Rule::in(['ios', 'android', 'web', 'expo', 'unknown'])],
        ];
    }
}
