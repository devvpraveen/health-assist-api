<?php

namespace App\Services\Auth\Providers;

use App\Contracts\Auth\EmailOtpProvider;
use App\Models\OtpChallenge;

class DevEmailOtpProvider extends DevOtpProvider implements EmailOtpProvider
{
    public function request(string $email, ?int $tenantId = null): array
    {
        return $this->createChallenge(OtpChallenge::CHANNEL_EMAIL, strtolower($email), $tenantId);
    }

    public function verify(string $challengeUuid, string $code): bool
    {
        return $this->verifyChallenge($challengeUuid, $code, OtpChallenge::CHANNEL_EMAIL);
    }
}
