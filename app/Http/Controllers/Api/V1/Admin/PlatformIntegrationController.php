<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformIntegration;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PlatformIntegrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request->user());
        $this->ensureDefaults();

        $rows = PlatformIntegration::query()->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'data' => $rows->map(fn (PlatformIntegration $i) => [
                'id' => (string) $i->key,
                'db_id' => $i->id,
                'key' => $i->key,
                'name' => $i->name,
                'category' => $i->category,
                'status' => $i->status,
                'driver' => $i->driver,
            ]),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:platform_integrations,key'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(PlatformIntegration::STATUSES)],
            'driver' => ['nullable', 'string', 'max:100'],
        ]);

        $integration = PlatformIntegration::query()->create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'status' => $validated['status'] ?? 'standby',
            'driver' => $validated['driver'] ?? null,
        ]);

        $auditLogger->log('platform.integrations.created', $integration, ['key' => $integration->key]);

        return response()->json([
            'data' => [
                'id' => $integration->key,
                'db_id' => $integration->id,
                'key' => $integration->key,
                'name' => $integration->name,
                'category' => $integration->category,
                'status' => $integration->status,
                'driver' => $integration->driver,
            ],
        ], 201);
    }

    public function update(Request $request, PlatformIntegration $integration, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(PlatformIntegration::STATUSES)],
            'driver' => ['nullable', 'string', 'max:100'],
        ]);

        $integration->update($validated);
        $auditLogger->log('platform.integrations.updated', $integration, ['key' => $integration->key]);

        return response()->json([
            'data' => [
                'id' => $integration->key,
                'db_id' => $integration->id,
                'key' => $integration->key,
                'name' => $integration->name,
                'category' => $integration->category,
                'status' => $integration->status,
                'driver' => $integration->driver,
            ],
        ]);
    }

    public function destroy(Request $request, PlatformIntegration $integration, AuditLogger $auditLogger): Response
    {
        $this->authorizeManage($request->user());

        $auditLogger->log('platform.integrations.deleted', $integration, ['key' => $integration->key]);
        $integration->delete();

        return response()->noContent();
    }

    private function ensureDefaults(): void
    {
        if (PlatformIntegration::query()->exists()) {
            return;
        }

        foreach ([
            ['key' => 'evolution', 'name' => 'Evolution API', 'category' => 'WhatsApp', 'status' => 'connected', 'driver' => 'evolution'],
            ['key' => 'meta', 'name' => 'Meta Cloud API', 'category' => 'WhatsApp', 'status' => 'standby', 'driver' => 'meta'],
            ['key' => 'razorpay', 'name' => 'Razorpay', 'category' => 'Payments', 'status' => 'connected', 'driver' => null],
            ['key' => 'ses', 'name' => 'Amazon SES', 'category' => 'Email', 'status' => 'standby', 'driver' => null],
        ] as $row) {
            PlatformIntegration::query()->create($row);
        }
    }

    private function authorizeManage(?User $user): void
    {
        abort_unless($user !== null && $user->isSuperAdmin(), 403);
    }
}
