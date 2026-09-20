<?php

namespace App\Services\Templates;

use App\Models\ContentTemplate;
use App\Models\TemplateVersion;
use Illuminate\Support\Arr;

class TemplateEngine
{
    /**
     * Resolve published template for a tenant (tenant override wins over platform).
     */
    public function resolvePublished(string $key, ?int $tenantId = null, ?string $type = null): ?ContentTemplate
    {
        $query = ContentTemplate::query()
            ->with('activeVersion')
            ->where('key', $key)
            ->where('status', ContentTemplate::STATUS_PUBLISHED)
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
     * Render template body / section text with {{dot.path}} placeholders.
     *
     * @param  array<string, mixed>  $context
     */
    public function render(TemplateVersion $version, array $context): string
    {
        $schema = $version->schema ?? [];
        $body = (string) ($schema['body'] ?? '');

        if ($body === '' && isset($schema['sections']) && is_array($schema['sections'])) {
            $parts = [];
            foreach ($schema['sections'] as $section) {
                if (! is_array($section)) {
                    continue;
                }
                $title = (string) ($section['title'] ?? '');
                $content = (string) ($section['body'] ?? '');
                $parts[] = trim($title !== '' ? "## {$title}\n{$content}" : $content);
            }
            $body = implode("\n\n", array_filter($parts));
        }

        return $this->interpolate($body, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function interpolate(string $text, array $context): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($context): string {
                $value = Arr::get($context, $matches[1]);
                if ($value === null) {
                    return '';
                }
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                if (is_scalar($value)) {
                    return (string) $value;
                }

                return '';
            },
            $text,
        );
    }
}
