<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\ChoosePersonaAction;
use App\Actions\Patients\EnsureLinkedPatientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\I18n\LanguageCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->string('device_name', 'api')->toString(),
        );

        $user = $result['user']->load(['roles.permissions', 'tenant']);

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->handle($request->user());

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request, LanguageCatalog $catalog, EnsureLinkedPatientAction $ensureLinkedPatient): UserResource
    {
        $user = $request->user()->load(['roles.permissions', 'tenant']);

        if ($user->tenant_id) {
            $ensureLinkedPatient->handle($user);
        }

        return (new UserResource($user))->additional([
            'meta' => [
                'available_languages' => [
                    'patient_app' => $catalog->enabledCodes('patient_app', $user->tenant_id),
                    'clinic_app' => $catalog->enabledCodes('clinic_app', $user->tenant_id),
                    'public_content' => $catalog->enabledCodes('public_content', $user->tenant_id),
                    'notifications' => $catalog->enabledCodes('notifications', $user->tenant_id),
                    'clinical_content' => $catalog->enabledCodes('clinical_content', $user->tenant_id),
                ],
                'needs_persona' => ! app(ChoosePersonaAction::class)->hasPersonaRole($user),
            ],
        ]);
    }

    public function choosePersona(Request $request, ChoosePersonaAction $action): JsonResponse
    {
        $data = $request->validate([
            'persona' => ['required', 'string', Rule::in(array_keys(ChoosePersonaAction::PERSONAS))],
            'name' => ['nullable', 'string', 'max:120'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:120'],
            'package_key' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $result = $action->handle($user, $data['persona'], [
            'name' => $data['name'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'package_key' => $data['package_key'] ?? 'free',
        ]);

        if ($result['user']->tenant?->uuid) {
            // Client should persist new tenant UUID after workspace provision.
        }

        return response()->json([
            'data' => new UserResource($result['user']),
            'meta' => [
                'persona' => $result['persona'],
                'role_slug' => $result['role_slug'],
                'org_type' => $result['org_type'] ?? null,
                'needs_persona' => false,
                'tenant_uuid' => $result['user']->tenant?->uuid,
            ],
        ]);
    }
}
