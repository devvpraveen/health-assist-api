<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class MobileExperienceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'user' => $data['user'],
            'account_types' => $data['account_types'],
            'active_account_type' => $data['active_account_type'],
            'professional_type' => $data['professional_type'],
            'active_workspace' => $data['active_workspace'] ?? null,
            'organization' => $data['organization'],
            'roles' => $data['roles'],
            'permissions' => $data['permissions'],
            'modules' => $data['modules'],
            'entitlements' => $data['entitlements'],
            'feature_flags' => $data['feature_flags'],
            'workspaces' => $data['workspaces'],
            'has_linked_patient' => $data['has_linked_patient'] ?? false,
        ];
    }
}
