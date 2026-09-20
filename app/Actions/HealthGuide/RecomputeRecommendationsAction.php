<?php

namespace App\Actions\HealthGuide;

use App\Models\HealthGuideConversation;
use App\Services\Appointments\AvailabilityService;
use App\Services\HealthGuide\HealthIntentExtractor;
use App\Services\Providers\ProviderRankingService;
use App\Services\Safety\SafetyEngine;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RecomputeRecommendationsAction
{
    public function __construct(
        private SafetyEngine $safetyEngine,
        private HealthIntentExtractor $intentExtractor,
        private ProviderRankingService $rankingService,
        private AvailabilityService $availabilityService,
    ) {}

    /**
     * @return array{
     *     conversation: HealthGuideConversation,
     *     structured_state: array<string, mixed>,
     *     safety: array<string, mixed>,
     *     recommendations: list<array<string, mixed>>,
     *     disclaimer: string
     * }
     */
    public function handle(HealthGuideConversation $conversation): array
    {
        $tenantId = TenantContext::id() ?? $conversation->tenant_id;
        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant' => ['Tenant context is required.'],
            ]);
        }

        $state = $conversation->structured_state ?? [];
        $lastUser = $conversation->messages()
            ->where('role', 'user')
            ->latest('id')
            ->first();

        $text = $lastUser?->content
            ?? implode(' ', array_filter([
                $state['complaint'] ?? null,
                $state['severity'] ?? null,
                $state['duration'] ?? null,
            ]));

        $safety = $this->safetyEngine->assess((string) $text, $state, [
            'persist' => false,
            'conversation_id' => $conversation->id,
            'patient_id' => $conversation->patient_id,
            'tenant_id' => $tenantId,
        ]);

        if (! $safety->allowsProviderRecommendations()) {
            return [
                'conversation' => $conversation,
                'structured_state' => $state,
                'safety' => $safety->toPayload(),
                'recommendations' => [],
                'disclaimer' => (string) config('health_guide.disclaimer'),
            ];
        }

        if (! $this->intentExtractor->hasEnoughInfoForRecommendations($state)) {
            return [
                'conversation' => $conversation,
                'structured_state' => $state,
                'safety' => $safety->toPayload(),
                'recommendations' => [],
                'disclaimer' => (string) config('health_guide.disclaimer'),
            ];
        }

        $ranked = $this->rankingService->rank((int) $tenantId, [
            'care_category' => $state['care_category'] ?? null,
            'complaint' => $state['complaint'] ?? null,
            'city' => $state['city'] ?? null,
        ]);

        $slotsLimit = (int) config('health_guide.ranking.next_slots_limit', 3);
        $days = (int) config('health_guide.ranking.next_slots_days', 7);
        $from = CarbonImmutable::now()->startOfDay();

        $recommendations = array_map(function (array $item) use ($slotsLimit, $days, $from): array {
            $nextSlots = [];
            if ($item['availability'] ?? false) {
                try {
                    $nextSlots = array_slice(
                        $this->availabilityService->slots((int) $item['id'], $from, $days),
                        0,
                        $slotsLimit,
                    );
                } catch (\Throwable) {
                    $nextSlots = [];
                }
            }
            $item['next_slots'] = $nextSlots;
            $item['explanation'] = sprintf(
                '%s scored %s (deterministic ranking).',
                $item['display_name'] ?? 'Provider',
                $item['score'] ?? 0,
            );

            return $item;
        }, $ranked);

        return [
            'conversation' => $conversation,
            'structured_state' => $state,
            'safety' => $safety->toPayload(),
            'recommendations' => $recommendations,
            'disclaimer' => (string) config('health_guide.disclaimer'),
        ];
    }
}
