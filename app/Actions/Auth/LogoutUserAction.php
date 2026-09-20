<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUserAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $this->auditLogger->log('auth.logout', $user, null, $user);
    }
}
