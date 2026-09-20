<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SecurityConsoleController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $this->authorizeView($request->user());
        $this->ensureDefaultAlerts();

        $alerts = SecurityAlert::query()->latest('id')->limit(50)->get()->map(fn (SecurityAlert $a) => [
            'id' => $a->uuid,
            'db_id' => $a->id,
            'severity' => $a->severity,
            'title' => $a->title,
            'detail' => $a->detail,
            'status' => $a->status,
            'created_at' => $a->created_at?->toIso8601String(),
        ]);

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => (string) $log->id,
                'actor' => $log->actor?->name ?? $log->actor?->email ?? 'system',
                'action' => $log->action,
                'target' => trim(($log->subject_type ? class_basename($log->subject_type) : 'n/a').' #'.($log->subject_id ?? '—')),
                'ip' => $log->ip,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        $accessLogs = $logs->filter(fn ($row) => str_contains($row['action'], 'login')
            || str_contains($row['action'], 'auth')
            || str_contains($row['action'], 'users.')
            || str_contains($row['action'], 'roles.')
            || str_contains($row['action'], 'permission'))->values();

        $permissionLogs = $logs->filter(fn ($row) => str_contains($row['action'], 'permission')
            || str_contains($row['action'], 'roles.'))->values();

        $webhookEvents = $logs->filter(fn ($row) => str_contains($row['action'], 'webhook')
            || str_contains($row['action'], 'whatsapp.'))->values();

        return response()->json([
            'data' => [
                'alerts' => $alerts,
                'accessLogs' => $accessLogs,
                'permissionLogs' => $permissionLogs,
                'webhookEvents' => $webhookEvents,
                'aiGovernance' => [
                    ['id' => 'gov-1', 'policy' => 'No diagnosis / no prescribe', 'status' => 'enforced'],
                    ['id' => 'gov-2', 'policy' => 'Emergency escalation required', 'status' => 'enforced'],
                    ['id' => 'gov-3', 'policy' => 'AI assistive-only disclaimer required', 'status' => 'enforced'],
                ],
            ],
        ]);
    }

    public function storeAlert(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeView($request->user());

        $validated = $request->validate([
            'severity' => ['required', 'string', Rule::in(SecurityAlert::SEVERITIES)],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(SecurityAlert::STATUSES)],
        ]);

        $alert = SecurityAlert::query()->create([
            'severity' => $validated['severity'],
            'title' => $validated['title'],
            'detail' => $validated['detail'] ?? null,
            'status' => $validated['status'] ?? 'open',
        ]);

        $auditLogger->log('security.alert.created', $alert, ['alert_uuid' => $alert->uuid]);

        return response()->json([
            'data' => [
                'id' => $alert->uuid,
                'db_id' => $alert->id,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'detail' => $alert->detail,
                'status' => $alert->status,
                'created_at' => $alert->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function updateAlert(Request $request, SecurityAlert $alert, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeView($request->user());

        $validated = $request->validate([
            'severity' => ['sometimes', 'string', Rule::in(SecurityAlert::SEVERITIES)],
            'title' => ['sometimes', 'string', 'max:255'],
            'detail' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(SecurityAlert::STATUSES)],
        ]);

        if (($validated['status'] ?? null) === 'resolved' && $alert->status !== 'resolved') {
            $validated['resolved_at'] = now();
        }

        $alert->update($validated);
        $auditLogger->log('security.alert.updated', $alert, ['alert_uuid' => $alert->uuid]);

        return response()->json([
            'data' => [
                'id' => $alert->uuid,
                'db_id' => $alert->id,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'detail' => $alert->detail,
                'status' => $alert->status,
                'created_at' => $alert->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroyAlert(Request $request, SecurityAlert $alert, AuditLogger $auditLogger): Response
    {
        $this->authorizeView($request->user());

        $auditLogger->log('security.alert.deleted', $alert, ['alert_uuid' => $alert->uuid]);
        $alert->delete();

        return response()->noContent();
    }

    private function ensureDefaultAlerts(): void
    {
        if (SecurityAlert::query()->exists()) {
            return;
        }

        SecurityAlert::query()->create([
            'severity' => 'medium',
            'title' => 'Security console online',
            'detail' => 'Platform security alerts are now persisted. Create alerts for operational incidents.',
            'status' => 'acknowledged',
        ]);
    }

    private function authorizeView(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('audit.view')),
            403,
        );
    }
}
