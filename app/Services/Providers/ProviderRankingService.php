<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Specialty;

/**
 * Deterministic, auditable provider ranking. AI must not modify scores.
 *
 * Limitations: distance, price, and reviews contribute 0 until data exists.
 */
class ProviderRankingService
{
    /**
     * @param  array{
     *     care_category?: string|null,
     *     specialty_slugs?: list<string>|null,
     *     city?: string|null,
     *     complaint?: string|null,
     *     complaint_keywords?: list<string>|null,
     *     language?: string|null,
     *     limit?: int|null,
     * }  $criteria
     * @return list<array<string, mixed>>
     */
    public function rank(int $tenantId, array $criteria = []): array
    {
        $weights = config('health_guide.ranking.weights', []);
        $limit = (int) ($criteria['limit'] ?? config('health_guide.ranking.max_recommendations', 5));
        $specialtySlugs = $this->resolveSpecialtySlugs($criteria);
        $complaintKeywords = $this->complaintKeywords($criteria);
        $city = isset($criteria['city']) ? str_replace('_', ' ', mb_strtolower((string) $criteria['city'])) : null;
        $language = isset($criteria['language']) ? mb_strtolower((string) $criteria['language']) : null;
        $maxExperience = max(1, (int) config('health_guide.ranking.max_experience_years', 30));

        $providers = Provider::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->where('is_public', true)
                    ->orWhere('status', 'active');
            })
            ->with(['specialties', 'clinic', 'schedules' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $ranked = $providers->map(function (Provider $provider) use (
            $weights,
            $specialtySlugs,
            $complaintKeywords,
            $city,
            $language,
            $maxExperience,
        ): array {
            return $this->scoreProvider(
                $provider,
                $weights,
                $specialtySlugs,
                $complaintKeywords,
                $city,
                $language,
                $maxExperience,
            );
        });

        return $ranked
            ->sort(function (array $a, array $b): int {
                if ($a['score'] === $b['score']) {
                    return $a['id'] <=> $b['id'];
                }

                return $b['score'] <=> $a['score'];
            })
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * @param  array<string, int|float>  $weights
     * @param  list<string>  $specialtySlugs
     * @param  list<string>  $complaintKeywords
     * @return array<string, mixed>
     */
    private function scoreProvider(
        Provider $provider,
        array $weights,
        array $specialtySlugs,
        array $complaintKeywords,
        ?string $city,
        ?string $language,
        int $maxExperience,
    ): array {
        $reasons = [];
        $score = 0.0;

        $providerSpecialtySlugs = $provider->specialties
            ->pluck('slug')
            ->map(fn ($s) => mb_strtolower((string) $s))
            ->all();

        $specialtyMatch = $specialtySlugs === []
            ? 0.4
            : (count(array_intersect($specialtySlugs, $providerSpecialtySlugs)) > 0 ? 1.0 : 0.0);

        if ($specialtyMatch >= 1.0) {
            $reasons[] = 'specialty_match';
        }

        $score += ((float) ($weights['specialty_match'] ?? 0)) * $specialtyMatch;

        $years = (int) ($provider->years_experience ?? 0);
        $experienceRatio = min(1.0, $years / $maxExperience);
        $score += ((float) ($weights['experience'] ?? 0)) * $experienceRatio;
        if ($years >= 5) {
            $reasons[] = 'experience';
        }

        $verified = $provider->verification_status === 'verified' ? 1.0 : 0.0;
        $score += ((float) ($weights['verification_status'] ?? 0)) * $verified;
        if ($verified === 1.0) {
            $reasons[] = 'verified';
        }

        $isPublic = $provider->is_public ? 1.0 : 0.0;
        $score += ((float) ($weights['is_public'] ?? 0)) * $isPublic;
        if ($isPublic === 1.0) {
            $reasons[] = 'public_profile';
        }

        $hasAvailability = $provider->schedules->contains(
            fn (Schedule $schedule): bool => (bool) $schedule->is_active
        );
        $score += ((float) ($weights['availability'] ?? 0)) * ($hasAvailability ? 1.0 : 0.0);
        if ($hasAvailability) {
            $reasons[] = 'has_schedule';
        }

        $languageMatch = 0.0;
        if ($language !== null) {
            $langs = collect($provider->languages ?? [])->map(fn ($l) => mb_strtolower((string) $l));
            $languageMatch = $langs->contains($language) ? 1.0 : 0.0;
            if ($languageMatch === 1.0) {
                $reasons[] = 'language_match';
            }
        }
        $score += ((float) ($weights['language_match'] ?? 0)) * $languageMatch;

        // Stubbed signals — documented limitation.
        $score += ((float) ($weights['distance'] ?? 0)) * 0.0;
        $score += ((float) ($weights['price'] ?? 0)) * 0.0;
        $score += ((float) ($weights['reviews'] ?? 0)) * 0.0;

        if ($city !== null && $provider->clinic && filled($provider->clinic->city)) {
            if (mb_strtolower((string) $provider->clinic->city) === $city) {
                $reasons[] = 'city_match';
                $score += 2.0;
            }
        }

        $conditionMatch = $this->conditionMatchHeuristic($provider, $complaintKeywords, $providerSpecialtySlugs);

        return [
            'id' => $provider->id,
            'uuid' => $provider->uuid,
            'display_name' => $provider->display_name ?: trim($provider->first_name.' '.$provider->last_name),
            'type' => $provider->type,
            'clinic_id' => $provider->clinic_id,
            'clinic_name' => $provider->clinic?->name,
            'city' => $provider->clinic?->city,
            'years_experience' => $provider->years_experience,
            'verification_status' => $provider->verification_status,
            'is_public' => $provider->is_public,
            'specialties' => $provider->specialties->map(fn (Specialty $s): array => [
                'id' => $s->id,
                'slug' => $s->slug,
                'name' => $s->name,
            ])->values()->all(),
            'score' => round($score, 4),
            'match_reason' => array_values(array_unique($reasons)),
            'specialty_match' => $specialtyMatch >= 1.0,
            'condition_match' => $conditionMatch,
            'availability' => $hasAvailability,
            'distance' => null,
            'explanation' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<string>
     */
    private function resolveSpecialtySlugs(array $criteria): array
    {
        if (! empty($criteria['specialty_slugs']) && is_array($criteria['specialty_slugs'])) {
            return array_values(array_map(fn ($s) => mb_strtolower((string) $s), $criteria['specialty_slugs']));
        }

        $care = mb_strtolower((string) ($criteria['care_category'] ?? ''));

        return match (true) {
            str_contains($care, 'physio') || str_contains($care, 'orthopedic') => ['physiotherapy', 'orthopedics'],
            str_contains($care, 'general') => ['general_medicine'],
            str_contains($care, 'dental') => ['dentistry'],
            str_contains($care, 'wellness') => ['wellness'],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<string>
     */
    private function complaintKeywords(array $criteria): array
    {
        $keywords = [];
        if (! empty($criteria['complaint_keywords']) && is_array($criteria['complaint_keywords'])) {
            $keywords = $criteria['complaint_keywords'];
        }
        if (! empty($criteria['complaint'])) {
            $keywords[] = str_replace('_', ' ', (string) $criteria['complaint']);
        }

        return array_values(array_unique(array_map(
            fn ($k) => mb_strtolower((string) $k),
            $keywords,
        )));
    }

    /**
     * @param  list<string>  $complaintKeywords
     * @param  list<string>  $providerSpecialtySlugs
     */
    private function conditionMatchHeuristic(Provider $provider, array $complaintKeywords, array $providerSpecialtySlugs): bool
    {
        if ($complaintKeywords === []) {
            return false;
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            $provider->bio,
            $provider->type,
            implode(' ', $providerSpecialtySlugs),
        ])));

        foreach ($complaintKeywords as $keyword) {
            $token = explode(' ', $keyword)[0] ?? $keyword;
            if ($token !== '' && str_contains($haystack, $token)) {
                return true;
            }
        }

        // Knee / MSK complaints align with physio/ortho specialties.
        $msk = ['knee', 'back', 'shoulder', 'neck', 'pain'];
        foreach ($complaintKeywords as $keyword) {
            foreach ($msk as $term) {
                if (str_contains($keyword, $term)
                    && (in_array('physiotherapy', $providerSpecialtySlugs, true)
                        || in_array('orthopedics', $providerSpecialtySlugs, true)
                        || $provider->type === 'physiotherapist')) {
                    return true;
                }
            }
        }

        return false;
    }
}
