<?php

namespace App\Services\Auth\Providers;

use App\Contracts\Auth\MobileOtpProvider;
use App\Models\OtpChallenge;

class DevMobileOtpProvider extends DevOtpProvider implements MobileOtpProvider
{
    public function request(string $mobile, ?int $tenantId = null): array
    {
        return $this->createChallenge(OtpChallenge::CHANNEL_MOBILE, $mobile, $tenantId);
    }

    public function verify(string $challengeUuid, string $code): bool
    {
        return $this->verifyChallenge($challengeUuid, $code, OtpChallenge::CHANNEL_MOBILE);
    }
}
