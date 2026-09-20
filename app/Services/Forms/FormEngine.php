<?php

namespace App\Services\Forms;

use App\Models\FormDefinition;
use App\Models\FormVersion;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FormEngine
{
    /**
     * Resolve published form for a tenant (tenant override wins over platform).
     */
    public function resolvePublished(string $key, ?int $tenantId = null, ?string $type = null): ?FormDefinition
    {
        $query = FormDefinition::query()
            ->with('activeVersion')
            ->where('key', $key)
            ->where('status', FormDefinition::STATUS_PUBLISHED)
            ->whereNotNull('active_version_id');

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($tenantId !== null) {
            $tenant = (clone $query)->where('owner_key', 'tenant:'.$tenantId)->first();
            if ($tenant !== null) {
                return $tenant;
            }
        }

        return $query->where('owner_key', 'platform')->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed> sanitized payload
     *
     * @throws ValidationException
     */
    public function validate(FormVersion $version, array $payload): array
    {
        $fields = $version->schema['fields'] ?? [];
        if (! is_array($fields)) {
            return $payload;
        }

        $errors = [];
        $out = [];

        foreach ($fields as $field) {
            if (! is_array($field) || empty($field['key'])) {
                continue;
            }

            $key = (string) $field['key'];
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);
            $visible = $this->isVisible($field, $payload);

            if (! $visible) {
                continue;
            }

            $value = Arr::get($payload, $key);
            $empty = $value === null || $value === '' || $value === [];

            if ($required && $empty) {
                $errors[$key][] = 'The '.$key.' field is required.';

                continue;
            }

            if ($empty) {
                continue;
            }

            $normalized = $this->normalizeValue($type, $value);
            if ($normalized === null && ! in_array($type, ['checkbox'], true)) {
                $errors[$key][] = "The {$key} field must be a valid {$type}.";

                continue;
            }

            if (isset($field['options']) && is_array($field['options']) && in_array($type, ['select', 'radio'], true)) {
                $allowed = array_map(fn ($o) => is_array($o) ? (string) ($o['value'] ?? '') : (string) $o, $field['options']);
                if (! in_array((string) $normalized, $allowed, true)) {
                    $errors[$key][] = "The selected {$key} is invalid.";

                    continue;
                }
            }

            if ($type === 'multiselect' && isset($field['options']) && is_array($field['options'])) {
                $allowed = array_map(fn ($o) => is_array($o) ? (string) ($o['value'] ?? '') : (string) $o, $field['options']);
                foreach ((array) $normalized as $item) {
                    if (! in_array((string) $item, $allowed, true)) {
                        $errors[$key][] = "The selected {$key} is invalid.";
                        break;
                    }
                }
            }

            if ($type === 'number' && isset($field['min']) && is_numeric($field['min']) && (float) $normalized < (float) $field['min']) {
                $errors[$key][] = "The {$key} field must be at least {$field['min']}.";
            }
            if ($type === 'number' && isset($field['max']) && is_numeric($field['max']) && (float) $normalized > (float) $field['max']) {
                $errors[$key][] = "The {$key} field must not be greater than {$field['max']}.";
            }

            $out[$key] = $normalized;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $payload
     */
    private function isVisible(array $field, array $payload): bool
    {
        $when = $field['visible_when'] ?? null;
        if (! is_array($when) || empty($when['field'])) {
            return true;
        }

        $actual = Arr::get($payload, (string) $when['field']);
        $equals = $when['equals'] ?? null;

        return $actual == $equals;
    }

    private function normalizeValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'number', 'rating', 'measurement' => is_numeric($value) ? 0 + $value : null,
            'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'multiselect' => is_array($value) ? array_values($value) : null,
            'date', 'datetime', 'text', 'textarea', 'select', 'radio', 'file', 'signature' => is_scalar($value) ? (string) $value : null,
            default => is_scalar($value) ? $value : null,
        };
    }
}
