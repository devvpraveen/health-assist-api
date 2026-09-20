<?php

namespace App\Services\Wellness;

use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use App\Models\WellnessContent;
use App\Models\WellnessRecommendationLog;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WellnessRecommendationEngine
{
    /**
     * Deterministic ranking of published wellness content for a patient.
     * Never invents medical claims; excludes is_clinical_advice=true.
     *
     * @return list<array{content: WellnessContent, score: float, reasons: list<string>}>
     */
    public function recommend(Patient $patient, int $limit = 10, bool $persistLogs = true): array
    {
        $limit = max(1, min(50, $limit));
        $tenantId = TenantContext::id() ?? $patient->tenant_id;

        /** @var PatientWellnessPreference|null $prefs */
        $prefs = PatientWellnessPreference::query()
            ->where('patient_id', $patient->id)
            ->first();

        $interests = array_values(array_filter($prefs?->interests ?? []));
        $excludedTags = array_values(array_filter($prefs?->excluded_tags ?? []));
        $goals = array_values(array_filter($prefs?->goals ?? []));

        $contents = WellnessContent::query()
            ->visibleToTenant($tenantId)
            ->published()
            ->wellnessOnly()
            ->with('category')
            ->get();

        $weights = config('wellness.recommendation');
        $now = CarbonImmutable::now();

        $ranked = $contents
            ->map(function (WellnessContent $content) use ($interests, $excludedTags, $goals, $weights, $now): array {
                $tags = array_values(array_filter($content->personalization_tags ?? []));
                $categorySlug = $content->category?->slug;

                if ($excludedTags !== [] && array_intersect($excludedTags, $tags) !== []) {
                    return [
                        'content' => $content,
                        'score' => -1.0,
                        'reasons' => ['excluded_by_tag'],
                    ];
                }

                $score = (float) ($weights['base_score'] ?? 10);
                $reasons = [];

                if ($categorySlug !== null && in_array($categorySlug, $interests, true)) {
                    $score += (float) ($weights['interest_match_weight'] ?? 40);
                    $reasons[] = 'interest_match:'.$categorySlug;
                }

                $tagOverlap = array_values(array_intersect($tags, array_merge($interests, $goals)));
                if ($tagOverlap !== []) {
                    $overlapScore = min(
                        (float) ($weights['tag_overlap_weight'] ?? 30),
                        count($tagOverlap) * 10.0,
                    );
                    $score += $overlapScore;
                    $reasons[] = 'tag_overlap:'.implode(',', $tagOverlap);
                }

                if ($content->published_at !== null) {
                    $days = max(0, $content->published_at->diffInDays($now));
                    $recency = max(0.0, (float) ($weights['recency_weight'] ?? 20) - ($days * 0.5));
                    $score += $recency;
                    if ($recency > 0) {
                        $reasons[] = 'recency';
                    }
                }

                if ($reasons === []) {
                    $reasons[] = 'base_catalog';
                }

                return [
                    'content' => $content,
                    'score' => round($score, 2),
                    'reasons' => $reasons,
                ];
            })
            ->filter(fn (array $row): bool => $row['score'] >= 0)
            ->sortByDesc(fn (array $row): float => $row['score'])
            ->values()
            ->take($limit);

        if ($persistLogs) {
            $this->persistLogs($patient, $ranked);
        }

        return $ranked->all();
    }

    /**
     * @param  Collection<int, array{content: WellnessContent, score: float, reasons: list<string>}>  $ranked
     */
    private function persistLogs(Patient $patient, Collection $ranked): void
    {
        $now = now();

        foreach ($ranked as $row) {
            WellnessRecommendationLog::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'content_id' => $row['content']->id,
                'score' => $row['score'],
                'reason' => ['reasons' => $row['reasons']],
                'created_at' => $now,
            ]);
        }
    }
}
