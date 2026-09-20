<?php

namespace App\Services\Auth\Providers;

use App\Models\OtpChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

abstract class DevOtpProvider
{
    /**
     * @return array{challenge_uuid: string, expires_at: string, destination: string, debug_code?: string}
     */
    protected function createChallenge(string $channel, string $destination, ?int $tenantId): array
    {
        $code = (string) config('auth_providers.dev_otp_code', '123456');
        $ttl = (int) config('auth_providers.otp_ttl_minutes', 10);

        $challenge = OtpChallenge::query()->create([
            'channel' => $channel,
            'destination' => $destination,
            'code_hash' => Hash::make($code),
            'tenant_id' => $tenantId,
            'expires_at' => now()->addMinutes($ttl),
            'meta' => ['driver' => 'dev'],
        ]);

        $payload = [
            'challenge_uuid' => $challenge->uuid,
            'expires_at' => $challenge->expires_at->toIso8601String(),
            'destination' => $destination,
        ];

        if (app()->environment(['local', 'testing']) || config('auth_providers.driver') === 'dev') {
            $payload['debug_code'] = $code;
        }

        return $payload;
    }

    protected function verifyChallenge(string $challengeUuid, string $code, string $expectedChannel): bool
    {
        $challenge = OtpChallenge::query()->where('uuid', $challengeUuid)->first();
        if ($challenge === null || $challenge->channel !== $expectedChannel) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or unknown verification challenge.'],
            ]);
        }

        if ($challenge->isConsumed()) {
            throw ValidationException::withMessages([
                'code' => ['This verification code was already used.'],
            ]);
        }

        if ($challenge->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['This verification code has expired.'],
            ]);
        }

        $maxAttempts = (int) config('auth_providers.otp_max_attempts', 5);
        if ($challenge->attempts >= $maxAttempts) {
            throw ValidationException::withMessages([
                'code' => ['Too many invalid attempts. Request a new code.'],
            ]);
        }

        $challenge->attempts = $challenge->attempts + 1;
        $challenge->save();

        if (! Hash::check($code, $challenge->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['Incorrect verification code.'],
            ]);
        }

        $challenge->consumed_at = now();
        $challenge->save();

        return true;
    }
}
