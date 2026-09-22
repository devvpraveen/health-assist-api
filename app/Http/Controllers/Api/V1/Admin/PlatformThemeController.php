<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Theme\PlatformThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformThemeController extends Controller
{
    public function show(Request $request, PlatformThemeService $themes): JsonResponse
    {
        $this->authorizeManage($request->user());

        return response()->json([
            'data' => $themes->adminPayload(),
        ]);
    }

    public function updateDraft(Request $request, PlatformThemeService $themes, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'preset' => ['sometimes', 'string', 'in:health_assist_default,clinical_blue,calm_teal,neutral_clinical,custom'],
            'brand' => ['sometimes', 'array'],
            'brand.appName' => ['sometimes', 'string', 'max:120'],
            'brand.organizationName' => ['sometimes', 'string', 'max:120'],
            'brand.logoUrl' => ['nullable', 'string', 'max:2048'],
            'brand.faviconUrl' => ['nullable', 'string', 'max:2048'],
            'colors' => ['sometimes', 'array'],
            'colors.primary' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.secondary' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.accent' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.background' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.surface' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.text' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.border' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.success' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.warning' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.error' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'typography' => ['sometimes', 'array'],
            'typography.headingFont' => ['sometimes', 'string', 'max:80'],
            'typography.bodyFont' => ['sometimes', 'string', 'max:80'],
            'typography.baseSize' => ['sometimes', 'integer', 'min:12', 'max:22'],
            'shape' => ['sometimes', 'array'],
            'shape.radiusPreset' => ['sometimes', 'string', 'in:clinical,soft,modern,compact'],
            'density' => ['sometimes', 'string', 'in:comfortable,standard,compact'],
        ]);

        if (isset($validated['colors']) && ($validated['preset'] ?? 'custom') === 'custom') {
            $validated['preset'] = 'custom';
        }

        $draft = $themes->updateDraft($validated, $request->user());
        $auditLogger->log('platform.theme.draft_updated', null, [
            'preset' => $draft['preset'] ?? null,
            'version' => $draft['version'] ?? null,
        ]);

        return response()->json([
            'data' => $themes->adminPayload(),
        ]);
    }

    public function publish(Request $request, PlatformThemeService $themes, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $published = $themes->publish($request->user());
        $auditLogger->log('platform.theme.published', null, [
            'version' => $published['version'] ?? null,
        ]);

        return response()->json([
            'data' => $themes->adminPayload(),
        ]);
    }

    private function authorizeManage(?User $user): void
    {
        abort_unless($user !== null && $user->isSuperAdmin(), 403);
    }
}
