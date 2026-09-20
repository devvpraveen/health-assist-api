<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PlatformSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request->user());
        $this->ensureDefaults();

        $rows = PlatformSetting::query()->orderBy('group')->orderBy('key')->get();

        return response()->json([
            'data' => $rows->map(fn (PlatformSetting $s) => [
                'id' => $s->id,
                'key' => $s->key,
                'label' => $s->label,
                'value' => $s->value,
                'group' => $s->group,
            ]),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:platform_settings,key'],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
            'group' => ['sometimes', 'string', 'max:100'],
        ]);

        $setting = PlatformSetting::query()->create([
            'key' => $validated['key'],
            'label' => $validated['label'],
            'value' => $validated['value'] ?? '',
            'group' => $validated['group'] ?? 'general',
        ]);

        $auditLogger->log('platform.settings.created', $setting, ['key' => $setting->key]);

        return response()->json(['data' => $setting], 201);
    }

    public function update(Request $request, PlatformSetting $setting, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
            'group' => ['sometimes', 'string', 'max:100'],
            'key' => [
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('platform_settings', 'key')->ignore($setting->id),
            ],
        ]);

        $setting->update($validated);
        $auditLogger->log('platform.settings.updated', $setting, ['key' => $setting->key]);

        return response()->json(['data' => $setting->fresh()]);
    }

    public function destroy(Request $request, PlatformSetting $setting, AuditLogger $auditLogger): Response
    {
        $this->authorizeManage($request->user());

        $auditLogger->log('platform.settings.deleted', $setting, ['key' => $setting->key]);
        $setting->delete();

        return response()->noContent();
    }

    private function ensureDefaults(): void
    {
        if (PlatformSetting::query()->exists()) {
            return;
        }

        $defaults = [
            ['key' => 'maintenance_mode', 'label' => 'Maintenance mode', 'value' => 'off', 'group' => 'general'],
            ['key' => 'default_locale', 'label' => 'Default locale', 'value' => 'en', 'group' => 'i18n'],
            ['key' => 'ai_kill_switch', 'label' => 'AI kill switch', 'value' => 'disabled', 'group' => 'ai'],
            ['key' => 'whatsapp_default_driver', 'label' => 'WhatsApp default driver', 'value' => 'evolution', 'group' => 'channels'],
            ['key' => 'content_unpublish_only', 'label' => 'Published content policy', 'value' => 'unpublish (no hard delete)', 'group' => 'content'],
        ];

        foreach ($defaults as $row) {
            PlatformSetting::query()->create($row);
        }
    }

    private function authorizeManage(?User $user): void
    {
        abort_unless($user !== null && $user->isSuperAdmin(), 403);
    }
}
