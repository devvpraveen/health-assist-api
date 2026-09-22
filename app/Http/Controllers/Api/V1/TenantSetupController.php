<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tenants\ResolveTenantSetupStatusAction;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSetupController extends Controller
{
    public function show(Request $request, ResolveTenantSetupStatusAction $action): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        return response()->json([
            'data' => $action->handle($user),
        ]);
    }

    public function dismissGettingStarted(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->tenant_id, 401);

        $organization = Organization::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('id')
            ->first();

        if ($organization) {
            $meta = $organization->meta ?? [];
            $meta['getting_started_dismissed'] = true;
            $organization->forceFill(['meta' => $meta])->save();
        }

        return response()->json([
            'data' => ['getting_started_dismissed' => true],
        ]);
    }
}
