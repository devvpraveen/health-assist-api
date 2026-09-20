<?php

namespace App\Http\Resources;

use App\Models\WellnessContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WellnessRecommendationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{content: WellnessContent, score: float, reasons: list<string>} $row */
        $row = $this->resource;

        return [
            'score' => $row['score'],
            'reasons' => $row['reasons'],
            'content' => new WellnessContentResource($row['content']),
            'disclaimer' => config('wellness.disclaimer'),
        ];
    }
}
