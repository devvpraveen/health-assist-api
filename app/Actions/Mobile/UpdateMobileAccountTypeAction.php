<?php

namespace App\Actions\Mobile;

use App\Models\MobileUserPreference;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Mobile\MobileExperienceBuilder;
use Illuminate\Support\Facades\DB;

class UpdateMobileAccountTypeAction
{
    public function __construct(
        private MobileExperienceBuilder $builder,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{account_type: string, professional_type?: string|null, active_workspace?: string|null, meta?: array<string, mixed>|null}  $data
     * @return array<string, mixed>
     */
    public function handle(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data): array {
            $preference = MobileUserPreference::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'account_type' => $data['account_type'],
                    'professional_type' => $data['professional_type'] ?? null,
                    'active_workspace' => $data['active_workspace'] ?? null,
                    'meta' => $data['meta'] ?? null,
                ],
            );

            $this->auditLogger->log('mobile.account_type.updated', $user, [
                'account_type' => $preference->account_type,
                'professional_type' => $preference->professional_type,
                'active_workspace' => $preference->active_workspace,
            ]);

            return $this->builder->build($user, $preference->fresh());
        });
    }
}
