<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrganizationReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = OrganizationReview::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(30);

        return response()->json($rows);
    }

    public function update(Request $request, OrganizationReview $review): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'published', 'hidden'])],
            'response' => ['nullable', 'string'],
        ]);

        if (array_key_exists('response', $data)) {
            $data['responded_at'] = $data['response'] ? now() : null;
        }

        $review->fill($data)->save();

        return response()->json(['data' => $review->fresh()]);
    }

    public function storePublic(Request $request, string $uuidOrSlug = ''): JsonResponse
    {
        $data = $request->validate([
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
            'author_name' => ['nullable', 'string', 'max:120'],
        ]);

        $clinic = null;
        if (! empty($uuidOrSlug)) {
            $clinic = \App\Models\Clinic::query()
                ->withoutGlobalScopes()
                ->where('is_public', true)
                ->where(function ($q) use ($uuidOrSlug): void {
                    $q->where('uuid', $uuidOrSlug)->orWhere('slug', $uuidOrSlug);
                })
                ->firstOrFail();
        } elseif (! empty($data['clinic_id'])) {
            $clinic = \App\Models\Clinic::query()->withoutGlobalScopes()->find($data['clinic_id']);
        }

        abort_unless($clinic, 422, 'Clinic required');

        $review = OrganizationReview::query()->create([
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'author_name' => $data['author_name'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $review], 201);
    }
}
