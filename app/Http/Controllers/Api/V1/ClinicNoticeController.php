<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClinicNotice;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicNoticeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = ClinicNotice::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(30);

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
        ]);

        $notice = ClinicNotice::query()->create([
            'tenant_id' => TenantContext::id() ?? $request->user()?->tenant_id,
            'clinic_id' => $data['clinic_id'] ?? null,
            'created_by_user_id' => $request->user()?->id,
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'is_public' => $data['is_public'] ?? true,
            'status' => $data['status'] ?? 'draft',
            'published_at' => ($data['status'] ?? '') === 'published' ? now() : null,
        ]);

        return response()->json(['data' => $notice], 201);
    }

    public function update(Request $request, ClinicNotice $notice): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if (($data['status'] ?? null) === 'published' && $notice->published_at === null) {
            $data['published_at'] = now();
        }

        $notice->fill($data)->save();

        return response()->json(['data' => $notice->fresh()]);
    }

    public function destroy(ClinicNotice $notice): JsonResponse
    {
        $notice->delete();

        return response()->json([], 204);
    }
}
