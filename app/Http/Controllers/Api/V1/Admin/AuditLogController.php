<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeView($request->user());

        $query = AuditLog::query()
            ->with(['actor:id,name,email', 'tenant:id,name,slug'])
            ->latest('id');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('action', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        $logs = $query->paginate(50);

        return response()->json([
            'data' => $logs->getCollection()->map(fn (AuditLog $log) => [
                'id' => (string) $log->id,
                'actor' => $log->actor?->name ?? $log->actor?->email ?? 'system',
                'action' => $log->action,
                'target' => trim(($log->subject_type ? class_basename($log->subject_type) : 'n/a').' #'.($log->subject_id ?? '—')),
                'ip' => $log->ip,
                'created_at' => $log->created_at?->toIso8601String(),
                'tenant' => $log->tenant?->name,
                'meta' => $log->meta,
            ])->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
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
