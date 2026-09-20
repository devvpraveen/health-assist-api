<?php

namespace App\Services\AI\Learning;

use App\Models\AiKnowledgeDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeRetriever
{
    /**
     * Keyword retrieval over approved knowledge only (embedding-ready stub).
     *
     * @return list<array{id: int, title: string, excerpt: string, source_type: string}>
     */
    public function retrieve(?int $tenantId, string $query, int $limit = 5): array
    {
        $terms = collect(preg_split('/\s+/', Str::lower($query)) ?: [])
            ->filter(fn ($t) => mb_strlen($t) > 2)
            ->take(8)
            ->values();

        if ($terms->isEmpty()) {
            return [];
        }

        $docs = AiKnowledgeDocument::query()
            ->where('status', AiKnowledgeDocument::STATUS_APPROVED)
            ->where(function ($q) use ($tenantId): void {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->latest('approved_at')
            ->limit(50)
            ->get();

        $scored = $docs->map(function (AiKnowledgeDocument $doc) use ($terms) {
            $hay = Str::lower($doc->title.' '.$doc->body);
            $score = $terms->sum(fn ($t) => substr_count($hay, $t));

            return ['doc' => $doc, 'score' => $score];
        })
            ->filter(fn ($row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $scored->map(function (array $row) {
            /** @var AiKnowledgeDocument $doc */
            $doc = $row['doc'];

            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'excerpt' => Str::limit(strip_tags($doc->body), 400),
                'source_type' => $doc->source_type,
            ];
        })->all();
    }

    public function formatContextBlock(array $hits): string
    {
        if ($hits === []) {
            return '';
        }

        $parts = ['Approved knowledge excerpts (cite cautiously; clinician review required):'];
        foreach ($hits as $hit) {
            $parts[] = '- '.$hit['title'].': '.$hit['excerpt'];
        }

        return implode("\n", $parts);
    }
}
