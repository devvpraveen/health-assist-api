<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\StaffInvite;
use App\Support\RoleCatalog;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffInviteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invites = StaffInvite::query()->latest()->paginate(30);
        $providers = Provider::query()->with('user')->orderBy('display_name')->limit(100)->get();

        return response()->json([
            'data' => [
                'invites' => $invites->items(),
                'providers' => $providers,
            ],
            'meta' => [
                'role_templates' => RoleCatalog::tenantRoleTemplatesMeta(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
            'role_slug' => ['nullable', Rule::in([
                'clinic_admin',
                'provider',
                'organization_admin',
                'branch_manager',
            ])],
            'designation' => ['nullable', 'string', 'max:120'],
        ]);

        $invite = StaffInvite::query()->create([
            'tenant_id' => TenantContext::id() ?? $request->user()?->tenant_id,
            'invited_by_user_id' => $request->user()?->id,
            'email' => strtolower($data['email']),
            'name' => $data['name'] ?? null,
            'role_slug' => $data['role_slug'] ?? 'provider',
            'designation' => $data['designation'] ?? null,
            'token' => Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(14),
        ]);

        return response()->json([
            'data' => $invite,
            'meta' => [
                'invite_url' => '/login?invite='.$invite->token,
            ],
        ], 201);
    }

    public function destroy(StaffInvite $invite): JsonResponse
    {
        $invite->forceFill(['status' => 'revoked'])->save();

        return response()->json(['data' => $invite->fresh()]);
    }
}
