<?php

namespace App\Services\I18n;

use App\Models\Language;
use App\Models\UiTranslation;
use Illuminate\Support\Facades\Cache;

class TranslationCatalog
{
    public const DEFAULT_LOCALE = 'en';

    /**
     * Flat key => value map for a locale (falls back to English for missing keys).
     *
     * @return array<string, string>
     */
    public function dictionary(string $locale, string $group = UiTranslation::GROUP_WEBSITE): array
    {
        $locale = strtolower(trim($locale)) ?: self::DEFAULT_LOCALE;
        $group = trim($group) ?: UiTranslation::GROUP_WEBSITE;

        return Cache::remember(
            $this->cacheKey($locale, $group),
            now()->addMinutes(10),
            function () use ($locale, $group): array {
                $catalogDefaults = $group === UiTranslation::GROUP_WEBSITE
                    ? WebsiteUiCatalog::english()
                    : [];
                $fallback = array_merge($catalogDefaults, $this->rowsFor(self::DEFAULT_LOCALE, $group));
                if ($locale === self::DEFAULT_LOCALE) {
                    return $fallback;
                }

                $localized = $this->rowsFor($locale, $group);

                return array_merge($fallback, $localized);
            },
        );
    }

    /**
     * @return list<array{id: int, group: string, key: string, locale: string, value: string}>
     */
    public function forAdmin(?string $group = null, ?string $locale = null): array
    {
        $group = $group ?: UiTranslation::GROUP_WEBSITE;

        $query = UiTranslation::query()
            ->where('group', $group)
            ->orderBy('key')
            ->orderBy('locale');

        if ($locale) {
            $query->where('locale', strtolower($locale));
        }

        /** @var array<string, array<string, UiTranslation>> $byKeyLocale */
        $byKeyLocale = [];
        foreach ($query->get() as $row) {
            $byKeyLocale[$row->key][$row->locale] = $row;
        }

        $catalogKeys = $group === UiTranslation::GROUP_WEBSITE
            ? WebsiteUiCatalog::keys()
            : [];
        $keys = array_values(array_unique([...$catalogKeys, ...array_keys($byKeyLocale)]));
        sort($keys);

        $englishDefaults = $group === UiTranslation::GROUP_WEBSITE
            ? WebsiteUiCatalog::english()
            : [];

        $result = [];
        foreach ($keys as $key) {
            $localeMap = $byKeyLocale[$key] ?? [];

            if ($locale) {
                $loc = strtolower($locale);
                if (isset($localeMap[$loc])) {
                    $row = $localeMap[$loc];
                    $result[] = [
                        'id' => $row->id,
                        'group' => $row->group,
                        'key' => $row->key,
                        'locale' => $row->locale,
                        'value' => $row->value,
                    ];
                } else {
                    $result[] = [
                        'id' => 0,
                        'group' => $group,
                        'key' => $key,
                        'locale' => $loc,
                        'value' => $loc === self::DEFAULT_LOCALE
                            ? (string) ($englishDefaults[$key] ?? '')
                            : '',
                    ];
                }
                continue;
            }

            if ($localeMap === []) {
                // Ensure every catalog key appears at least once for the matrix UI.
                $result[] = [
                    'id' => 0,
                    'group' => $group,
                    'key' => $key,
                    'locale' => self::DEFAULT_LOCALE,
                    'value' => (string) ($englishDefaults[$key] ?? ''),
                ];
                continue;
            }

            foreach ($localeMap as $row) {
                $result[] = [
                    'id' => $row->id,
                    'group' => $row->group,
                    'key' => $row->key,
                    'locale' => $row->locale,
                    'value' => $row->value,
                ];
            }

            // If English is missing but the key exists in another locale, still surface EN default.
            if (! isset($localeMap[self::DEFAULT_LOCALE]) && isset($englishDefaults[$key])) {
                $result[] = [
                    'id' => 0,
                    'group' => $group,
                    'key' => $key,
                    'locale' => self::DEFAULT_LOCALE,
                    'value' => $englishDefaults[$key],
                ];
            }
        }

        return $result;
    }

    /**
     * @param  list<array{group?: string, key: string, locale: string, value: string}>  $rows
     * @return list<array{id: int, group: string, key: string, locale: string, value: string}>
     */
    public function upsertMany(array $rows): array
    {
        $saved = [];

        foreach ($rows as $row) {
            $group = (string) ($row['group'] ?? UiTranslation::GROUP_WEBSITE);
            $key = (string) $row['key'];
            $locale = strtolower((string) $row['locale']);
            $value = (string) ($row['value'] ?? '');

            $model = UiTranslation::query()->updateOrCreate(
                ['group' => $group, 'key' => $key, 'locale' => $locale],
                ['value' => $value],
            );

            $saved[] = [
                'id' => $model->id,
                'group' => $model->group,
                'key' => $model->key,
                'locale' => $model->locale,
                'value' => $model->value,
            ];

            $this->forget($locale, $group);
            if ($locale !== self::DEFAULT_LOCALE) {
                $this->forget(self::DEFAULT_LOCALE, $group);
            }
        }

        return $saved;
    }

    public function forget(string $locale, string $group = UiTranslation::GROUP_WEBSITE): void
    {
        Cache::forget($this->cacheKey($locale, $group));
    }

    public function forgetGroup(string $group = UiTranslation::GROUP_WEBSITE): void
    {
        $locales = Language::query()->pluck('code')->all();
        foreach (array_unique([...$locales, self::DEFAULT_LOCALE]) as $locale) {
            $this->forget((string) $locale, $group);
        }
    }

    /**
     * @return array<string, string>
     */
    private function rowsFor(string $locale, string $group): array
    {
        return UiTranslation::query()
            ->where('group', $group)
            ->where('locale', $locale)
            ->pluck('value', 'key')
            ->all();
    }

    private function cacheKey(string $locale, string $group): string
    {
        return "ui_translations:{$group}:{$locale}";
    }
}
