<?php

namespace App\Services\HealthGuide;

/**
 * Deterministic heuristic extractor for Health Guide structured_state.
 * Optional LLM enrichment may add hints, but heuristics always win on merge.
 */
class HealthIntentExtractor
{
    /**
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>|null  $llmHints
     * @return array<string, mixed>
     */
    public function extract(string $text, ?array $existing = null, ?array $llmHints = null): array
    {
        $normalized = mb_strtolower(trim($text));
        $state = is_array($existing) ? $existing : [];

        $heuristic = [
            'intent' => $this->detectIntent($normalized, $state),
            'complaint' => $this->detectComplaint($normalized) ?? ($state['complaint'] ?? null),
            'duration' => $this->detectDuration($normalized) ?? ($state['duration'] ?? null),
            'severity' => $this->detectSeverity($normalized) ?? ($state['severity'] ?? null),
            'care_category' => null,
            'urgency' => null,
            'missing_fields' => [],
            'city' => $this->detectCity($normalized) ?? ($state['city'] ?? null),
            'locale_hint' => $state['locale_hint'] ?? null,
        ];

        $heuristic['care_category'] = $this->detectCareCategory(
            $heuristic['complaint'],
            $normalized
        ) ?? ($state['care_category'] ?? null);

        $heuristic['urgency'] = $this->detectUrgency(
            $heuristic['severity'],
            $normalized
        ) ?? ($state['urgency'] ?? null);

        // Heuristics first; LLM hints only fill nulls (never override).
        if (is_array($llmHints)) {
            foreach (['intent', 'complaint', 'duration', 'severity', 'care_category', 'urgency', 'city'] as $key) {
                if (($heuristic[$key] ?? null) === null && filled($llmHints[$key] ?? null)) {
                    $heuristic[$key] = $llmHints[$key];
                }
            }
        }

        $heuristic['missing_fields'] = $this->missingFields($heuristic);

        return array_filter(
            $heuristic,
            fn ($value) => $value !== null && $value !== [],
        ) + ['missing_fields' => $heuristic['missing_fields']];
    }

    public function hasEnoughInfoForRecommendations(array $state): bool
    {
        return filled($state['complaint'] ?? null)
            && filled($state['care_category'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    private function missingFields(array $state): array
    {
        $missing = [];
        foreach (['complaint', 'duration', 'severity'] as $field) {
            if (empty($state[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function detectIntent(string $text, array $state): string
    {
        if (preg_match('/\b(book|appointment|schedule|find (a )?(doctor|provider|clinic|physio)|recommend)\b/u', $text)) {
            return 'find_provider';
        }

        if (($state['intent'] ?? null) === 'find_provider') {
            return 'find_provider';
        }

        if ($this->detectComplaint($text) !== null) {
            return 'find_provider';
        }

        return $state['intent'] ?? 'general_guidance';
    }

    private function detectComplaint(string $text): ?string
    {
        $map = [
            'knee_pain' => ['knee pain', 'knee hurt', 'sore knee', 'knee ache'],
            'back_pain' => ['back pain', 'lower back', 'backache'],
            'shoulder_pain' => ['shoulder pain', 'frozen shoulder'],
            'neck_pain' => ['neck pain', 'stiff neck'],
            'chest_pain' => ['chest pain', 'pain in chest'],
            'headache' => ['headache', 'migraine'],
            'breathing_difficulty' => ['difficulty breathing', 'shortness of breath', 'cant breathe', "can't breathe"],
            'bleeding' => ['bleeding', 'blood loss'],
            'fever' => ['fever', 'high temperature'],
        ];

        foreach ($map as $code => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($text, $phrase)) {
                    return $code;
                }
            }
        }

        if (preg_match('/\b(pain|ache|hurt)\b/u', $text)) {
            return 'pain_unspecified';
        }

        return null;
    }

    private function detectDuration(string $text): ?string
    {
        if (preg_match('/\b(\d+)\s*weeks?\b/u', $text, $m)) {
            return $m[1].'_weeks';
        }
        if (preg_match('/\b(\d+)\s*days?\b/u', $text, $m)) {
            return $m[1].'_days';
        }
        if (preg_match('/\b(\d+)\s*months?\b/u', $text, $m)) {
            return $m[1].'_months';
        }
        if (preg_match('/\b(two|2)\s+weeks?\b/u', $text)) {
            return '2_weeks';
        }
        if (str_contains($text, 'chronic') || str_contains($text, 'for years')) {
            return 'chronic';
        }

        return null;
    }

    private function detectSeverity(string $text): ?string
    {
        if (preg_match('/\b(severe|excruciating|unbearable|worst)\b/u', $text)) {
            return 'severe';
        }
        if (preg_match('/\b(moderate|medium)\b/u', $text)) {
            return 'moderate';
        }
        if (preg_match('/\b(mild|slight|minor)\b/u', $text)) {
            return 'mild';
        }

        return null;
    }

    private function detectCareCategory(?string $complaint, string $text): ?string
    {
        return match ($complaint) {
            'knee_pain', 'back_pain', 'shoulder_pain', 'neck_pain' => 'orthopedic_or_physiotherapy',
            'chest_pain', 'breathing_difficulty' => 'emergency_or_general_medicine',
            'headache', 'fever' => 'general_medicine',
            'bleeding' => 'emergency_or_general_medicine',
            default => preg_match('/\b(physio|physiotherapy|ortho|orthopedic)\b/u', $text)
                ? 'orthopedic_or_physiotherapy'
                : null,
        };
    }

    private function detectUrgency(?string $severity, string $text): ?string
    {
        if (preg_match('/\b(chest pain|difficulty breathing|stroke|suicid|kill myself|uncontrolled bleeding)\b/u', $text)) {
            return 'emergency';
        }

        if ($severity === 'severe') {
            return 'requires_clinician_review';
        }

        if ($severity === 'mild') {
            return 'routine';
        }

        return null;
    }

    private function detectCity(string $text): ?string
    {
        if (preg_match('/\bin\s+([a-z][a-z\s]{2,30})\b/u', $text, $m)) {
            $candidate = trim($m[1]);
            $stop = ['the', 'a', 'an', 'my', 'pain', 'weeks', 'days'];
            if (! in_array($candidate, $stop, true)) {
                return str_replace(' ', '_', $candidate);
            }
        }

        return null;
    }
}
