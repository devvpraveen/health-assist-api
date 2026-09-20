<?php

namespace App\Services\Marketing;

use App\Models\Clinic;
use App\Models\SeoEntity;
use Illuminate\Support\Str;

class ShareMetaService
{
    /**
     * @return array{title: string, description: string, url: string, image: string|null, type: string}|null
     */
    public function resolve(string $type, ?string $slug = null, ?string $entityType = null, ?string $locale = 'en'): ?array
    {
        $siteUrl = rtrim((string) config('marketing.public_site_url'), '/');

        return match ($type) {
            'seo_entity' => $this->seoEntityMeta($siteUrl, (string) $slug, (string) $entityType, $locale ?? 'en'),
            'clinic' => $this->clinicMeta($siteUrl, (string) $slug),
            default => null,
        };
    }

    /**
     * @return array{title: string, description: string, url: string, image: string|null, type: string}|null
     */
    private function seoEntityMeta(string $siteUrl, string $slug, string $entityType, string $locale): ?array
    {
        if ($slug === '' || $entityType === '') {
            return null;
        }

        $entity = SeoEntity::query()
            ->published()
            ->whereNotNull('last_reviewed_at')
            ->where('type', $entityType)
            ->where('slug', $slug)
            ->where('locale', $locale)
            ->first();

        if (! $entity) {
            return null;
        }

        $segment = match ($entity->type) {
            SeoEntity::TYPE_CONDITION => 'conditions',
            SeoEntity::TYPE_SYMPTOM => 'symptoms',
            SeoEntity::TYPE_SPECIALTY => 'specialties',
            SeoEntity::TYPE_TREATMENT => 'treatments',
            default => 'knowledge',
        };

        return [
            'title' => $entity->seo_title ?: $entity->title,
            'description' => Str::limit($entity->seo_description ?: $entity->summary, 300),
            'url' => "{$siteUrl}/{$locale}/{$segment}/{$entity->slug}",
            'image' => null,
            'type' => 'seo_entity',
        ];
    }

    /**
     * @return array{title: string, description: string, url: string, image: string|null, type: string}|null
     */
    private function clinicMeta(string $siteUrl, string $slugOrUuid): ?array
    {
        if ($slugOrUuid === '') {
            return null;
        }

        $clinic = Clinic::query()
            ->withoutGlobalScopes()
            ->where('is_public', true)
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('slug', $slugOrUuid)->orWhere('uuid', $slugOrUuid))
            ->first();

        if (! $clinic) {
            return null;
        }

        return [
            'title' => $clinic->name.' | Health Assist',
            'description' => Str::limit((string) ($clinic->description ?: 'Find care at '.$clinic->name), 300),
            'url' => "{$siteUrl}/en/clinics/{$clinic->slug}",
            'image' => $clinic->logo_path ? url($clinic->logo_path) : null,
            'type' => 'clinic',
        ];
    }
}
