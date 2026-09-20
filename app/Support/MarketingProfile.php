<?php

namespace App\Support;

/**
 * Shared marketing profile shape stored on Clinic/Organization meta.profile.
 *
 * @phpstan-type Highlight array{label: string, value: string}
 * @phpstan-type Testimonial array{quote: string, author: string, role?: string|null}
 * @phpstan-type Profile array{
 *   tagline?: string|null,
 *   hero_headline?: string|null,
 *   hero_subheadline?: string|null,
 *   hero_image_url?: string|null,
 *   about?: string|null,
 *   booking_benefits?: list<string>,
 *   highlights?: list<Highlight>,
 *   testimonials?: list<Testimonial>,
 *   gallery?: list<string>,
 *   cta_label?: string|null,
 *   cta_href?: string|null,
 *   seo_title?: string|null,
 *   seo_description?: string|null,
 * }
 */
class MarketingProfile
{
    /**
     * @param  array<string, mixed>|null  $meta
     * @return Profile
     */
    public static function fromMeta(?array $meta): array
    {
        $profile = is_array($meta['profile'] ?? null) ? $meta['profile'] : [];

        return [
            'tagline' => self::stringOrNull($profile['tagline'] ?? null),
            'hero_headline' => self::stringOrNull($profile['hero_headline'] ?? null),
            'hero_subheadline' => self::stringOrNull($profile['hero_subheadline'] ?? null),
            'hero_image_url' => self::stringOrNull($profile['hero_image_url'] ?? null),
            'about' => self::stringOrNull($profile['about'] ?? null),
            'booking_benefits' => self::stringList($profile['booking_benefits'] ?? null),
            'highlights' => self::highlights($profile['highlights'] ?? null),
            'testimonials' => self::testimonials($profile['testimonials'] ?? null),
            'gallery' => self::stringList($profile['gallery'] ?? null),
            'cta_label' => self::stringOrNull($profile['cta_label'] ?? null),
            'cta_href' => self::stringOrNull($profile['cta_href'] ?? null),
            'seo_title' => self::stringOrNull($profile['seo_title'] ?? null),
            'seo_description' => self::stringOrNull($profile['seo_description'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>|null  $existingMeta
     * @return array<string, mixed>
     */
    public static function mergeIntoMeta(array $incoming, ?array $existingMeta): array
    {
        $meta = $existingMeta ?? [];
        $current = is_array($meta['profile'] ?? null) ? $meta['profile'] : [];
        $meta['profile'] = array_merge($current, self::fromMeta(['profile' => $incoming]));

        return $meta;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values($out);
    }

    /**
     * @return list<Highlight>
     */
    private static function highlights(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = self::stringOrNull($row['label'] ?? null);
            $val = self::stringOrNull($row['value'] ?? null);
            if ($label && $val) {
                $out[] = ['label' => $label, 'value' => $val];
            }
        }

        return $out;
    }

    /**
     * @return list<Testimonial>
     */
    private static function testimonials(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }
            $quote = self::stringOrNull($row['quote'] ?? null);
            $author = self::stringOrNull($row['author'] ?? null);
            if ($quote && $author) {
                $out[] = [
                    'quote' => $quote,
                    'author' => $author,
                    'role' => self::stringOrNull($row['role'] ?? null),
                ];
            }
        }

        return $out;
    }
}
