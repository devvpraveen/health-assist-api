<?php

namespace App\Services\Theme;

use App\Models\PlatformTheme;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformThemeService
{
    /**
     * Health Assist Default brand config (Deep Navy + Teal + Cyan).
     *
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return [
            'version' => 1,
            'preset' => 'health_assist_default',
            'brand' => [
                'appName' => 'Health Assist',
                'organizationName' => 'Health Assist',
                'logoUrl' => null,
                'faviconUrl' => null,
            ],
            'colors' => [
                'primary' => '#091E3A',
                'secondary' => '#0D9488',
                'accent' => '#0EA5E9',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'text' => '#091E3A',
                'border' => '#E2E8F0',
                'success' => '#059669',
                'warning' => '#D97706',
                'error' => '#DC2626',
            ],
            'typography' => [
                'headingFont' => 'Manrope',
                'bodyFont' => 'Inter',
                'baseSize' => 16,
            ],
            'shape' => [
                'radiusPreset' => 'modern',
            ],
            'density' => 'comfortable',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publishedConfig(): array
    {
        $published = PlatformTheme::query()
            ->where('channel', PlatformTheme::CHANNEL_PUBLISHED)
            ->first();

        if ($published === null) {
            return $this->defaultConfig();
        }

        return $this->normalizeConfig($published->config, $published->version);
    }

    /**
     * @return array{draft: array<string, mixed>, published: array<string, mixed>, draft_meta: array<string, mixed>, published_meta: array<string, mixed>}
     */
    public function adminPayload(): array
    {
        $this->ensureRows();

        $draft = PlatformTheme::query()->where('channel', PlatformTheme::CHANNEL_DRAFT)->firstOrFail();
        $published = PlatformTheme::query()->where('channel', PlatformTheme::CHANNEL_PUBLISHED)->firstOrFail();

        return [
            'draft' => $this->normalizeConfig($draft->config, $draft->version),
            'published' => $this->normalizeConfig($published->config, $published->version),
            'draft_meta' => [
                'version' => $draft->version,
                'updated_at' => $draft->updated_at?->toIso8601String(),
                'updated_by' => $draft->updated_by,
            ],
            'published_meta' => [
                'version' => $published->version,
                'published_at' => $published->published_at?->toIso8601String(),
                'updated_by' => $published->updated_by,
            ],
            'presets' => [
                'health_assist_default',
                'clinical_blue',
                'calm_teal',
                'neutral_clinical',
                'custom',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updateDraft(array $input, ?User $actor = null): array
    {
        $this->ensureRows();
        $draft = PlatformTheme::query()->where('channel', PlatformTheme::CHANNEL_DRAFT)->firstOrFail();
        $merged = $this->normalizeConfig(array_replace_recursive($draft->config, $input), $draft->version);
        $merged['preset'] = $input['preset'] ?? $merged['preset'];
        if (($merged['preset'] ?? '') !== 'custom' && isset($input['preset']) && $input['preset'] !== 'custom') {
            $merged['colors'] = $this->presetColors((string) $merged['preset']);
        }

        $draft->update([
            'config' => $merged,
            'updated_by' => $actor?->id,
        ]);

        return $this->normalizeConfig($draft->fresh()->config, $draft->version);
    }

    /**
     * @return array<string, mixed>
     */
    public function publish(?User $actor = null): array
    {
        return DB::transaction(function () use ($actor): array {
            $this->ensureRows();
            $draft = PlatformTheme::query()->where('channel', PlatformTheme::CHANNEL_DRAFT)->lockForUpdate()->firstOrFail();
            $published = PlatformTheme::query()->where('channel', PlatformTheme::CHANNEL_PUBLISHED)->lockForUpdate()->firstOrFail();

            $nextVersion = max($published->version, $draft->version) + 1;
            $config = $this->normalizeConfig($draft->config, $nextVersion);
            $config['version'] = $nextVersion;

            $published->update([
                'config' => $config,
                'version' => $nextVersion,
                'updated_by' => $actor?->id,
                'published_at' => now(),
            ]);

            $draft->update([
                'config' => $config,
                'version' => $nextVersion,
                'updated_by' => $actor?->id,
            ]);

            return $config;
        });
    }

    public function ensureRows(): void
    {
        $defaults = $this->defaultConfig();

        PlatformTheme::query()->firstOrCreate(
            ['channel' => PlatformTheme::CHANNEL_DRAFT],
            ['version' => 1, 'config' => $defaults],
        );

        PlatformTheme::query()->firstOrCreate(
            ['channel' => PlatformTheme::CHANNEL_PUBLISHED],
            ['version' => 1, 'config' => $defaults, 'published_at' => now()],
        );
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>
     */
    public function normalizeConfig(?array $config, int $version = 1): array
    {
        $base = $this->defaultConfig();
        $merged = array_replace_recursive($base, $config ?? []);
        $merged['version'] = (int) ($config['version'] ?? $version);

        foreach (['primary', 'secondary', 'accent', 'background', 'surface', 'text', 'border', 'success', 'warning', 'error'] as $key) {
            $value = $merged['colors'][$key] ?? $base['colors'][$key];
            if (! is_string($value) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                $merged['colors'][$key] = $base['colors'][$key];
            }
        }

        $allowedPresets = ['health_assist_default', 'clinical_blue', 'calm_teal', 'neutral_clinical', 'custom'];
        if (! in_array($merged['preset'] ?? '', $allowedPresets, true)) {
            $merged['preset'] = 'health_assist_default';
        }

        $allowedRadius = ['clinical', 'soft', 'modern', 'compact'];
        if (! in_array($merged['shape']['radiusPreset'] ?? '', $allowedRadius, true)) {
            $merged['shape']['radiusPreset'] = 'modern';
        }

        $allowedDensity = ['comfortable', 'standard', 'compact'];
        if (! in_array($merged['density'] ?? '', $allowedDensity, true)) {
            $merged['density'] = 'comfortable';
        }

        return $merged;
    }

    /**
     * @return array<string, string>
     */
    private function presetColors(string $preset): array
    {
        return match ($preset) {
            'clinical_blue' => [
                'primary' => '#0F2D59',
                'secondary' => '#1D4ED8',
                'accent' => '#0284C7',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'text' => '#0F172A',
                'border' => '#CBD5E1',
                'success' => '#059669',
                'warning' => '#D97706',
                'error' => '#DC2626',
            ],
            'calm_teal' => [
                'primary' => '#0F766E',
                'secondary' => '#0D9488',
                'accent' => '#0891B2',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'text' => '#134E4A',
                'border' => '#CCFBF1',
                'success' => '#059669',
                'warning' => '#D97706',
                'error' => '#DC2626',
            ],
            'neutral_clinical' => [
                'primary' => '#334155',
                'secondary' => '#475569',
                'accent' => '#2563EB',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'text' => '#0F172A',
                'border' => '#E2E8F0',
                'success' => '#059669',
                'warning' => '#D97706',
                'error' => '#DC2626',
            ],
            default => $this->defaultConfig()['colors'],
        };
    }
}
