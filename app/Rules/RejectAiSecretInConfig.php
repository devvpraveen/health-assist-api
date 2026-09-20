<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RejectAiSecretInConfig implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        if (array_key_exists('api_key', $value)) {
            $fail('Use env / provider secrets, not model config');
        }
    }
}
