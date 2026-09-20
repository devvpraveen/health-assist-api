<?php

namespace App\Contracts\Auth;

interface EmailOtpProvider
{
    /**
     * @return array{challenge_uuid: string, expires_at: string, destination: string, debug_code?: string}
     */
    public function request(string $email, ?int $tenantId = null): array;

    public function verify(string $challengeUuid, string $code): bool;
}
