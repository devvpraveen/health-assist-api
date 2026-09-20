<?php

namespace App\Actions\Auth;

use App\Actions\HealthGuide\ClaimGuestConversationAction;
use App\Contracts\Auth\EmailOtpProvider;
use App\Contracts\Auth\GoogleAuthProvider;
use App\Contracts\Auth\MobileOtpProvider;
use App\Models\GuestSession;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProgressiveAuthAction
{
    public function __construct(
        private MobileOtpProvider $mobileOtpProvider,
        private EmailOtpProvider $emailOtpProvider,
        private GoogleAuthProvider $googleAuthProvider,
        private ClaimGuestConversationAction $claimGuestConversationAction,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{challenge_uuid: string, expires_at: string, destination: string, debug_code?: string}
     */
    public function requestMobileOtp(string $mobile): array
    {
        return $this->mobileOtpProvider->request(
            $this->normalizeMobile($mobile),
            TenantContext::id(),
        );
    }

    /**
     * @return array{challenge_uuid: string, expires_at: string, destination: string, debug_code?: string}
     */
    public function requestEmailOtp(string $email): array
    {
        return $this->emailOtpProvider->request(
            strtolower(trim($email)),
            TenantContext::id(),
        );
    }

    /**
     * @return array{token: string, user: User, migration: array<string, mixed>|null}
     */
    public function verifyMobileOtp(
        string $challengeUuid,
        string $code,
        ?string $guestSessionUuid = null,
        string $deviceName = 'api',
    ): array {
        $this->mobileOtpProvider->verify($challengeUuid, $code);
        $challenge = OtpChallenge::query()->where('uuid', $challengeUuid)->firstOrFail();
        $user = $this->findOrCreateUserByPhone($challenge->destination, $challenge->tenant_id);

        return $this->issueSession($user, $deviceName, 'mobile_otp', $guestSessionUuid);
    }

    /**
     * @return array{token: string, user: User, migration: array<string, mixed>|null}
     */
    public function verifyEmailOtp(
        string $challengeUuid,
        string $code,
        ?string $guestSessionUuid = null,
        string $deviceName = 'api',
    ): array {
        $this->emailOtpProvider->verify($challengeUuid, $code);
        $challenge = OtpChallenge::query()->where('uuid', $challengeUuid)->firstOrFail();
        $user = $this->findOrCreateUserByEmail($challenge->destination, $challenge->tenant_id);

        return $this->issueSession($user, $deviceName, 'email_otp', $guestSessionUuid);
    }

    /**
     * @return array{token: string, user: User, migration: array<string, mixed>|null}
     */
    public function authenticateGoogle(
        string $idToken,
        ?string $guestSessionUuid = null,
        string $deviceName = 'api',
    ): array {
        $profile = $this->googleAuthProvider->verifyIdToken($idToken);
        $user = $this->findOrCreateUserByEmail(
            $profile['email'],
            TenantContext::id(),
            $profile['name'] ?? null,
        );

        return $this->issueSession($user, $deviceName, 'google', $guestSessionUuid);
    }

    /**
     * @return array{token: string, user: User, migration: array<string, mixed>|null}
     */
    private function issueSession(
        User $user,
        string $deviceName,
        string $method,
        ?string $guestSessionUuid,
    ): array {
        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($deviceName)->plainTextToken;

        $migration = null;
        if ($guestSessionUuid) {
            $session = GuestSession::query()
                ->withoutGlobalScopes()
                ->where('uuid', $guestSessionUuid)
                ->first();

            if ($session === null) {
                throw ValidationException::withMessages([
                    'guest_session_id' => ['Guest session not found.'],
                ]);
            }

            $claimed = $this->claimGuestConversationAction->handle($user, $session);
            $migration = [
                'migrated' => $claimed['migrated'],
                'message' => $claimed['message'],
                'conversation_uuid' => $claimed['conversation']->uuid,
                'guest_session_uuid' => $claimed['guest_session']->uuid,
            ];
        }

        $this->auditLogger->log('auth.progressive.login', $user, [
            'method' => $method,
            'guest_session_uuid' => $guestSessionUuid,
        ], $user);

        return [
            'token' => $token,
            'user' => $user->fresh(['tenant']),
            'migration' => $migration,
        ];
    }

    private function findOrCreateUserByPhone(string $mobile, ?int $tenantId): User
    {
        $user = User::query()->where('phone', $mobile)->first();
        if ($user) {
            return $user;
        }

        return User::query()->create([
            'name' => 'Patient',
            'email' => 'mobile+'.preg_replace('/\D+/', '', $mobile).'@users.healthassist.local',
            'phone' => $mobile,
            'password' => Hash::make(Str::random(32)),
            'tenant_id' => $tenantId ?? TenantContext::id(),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function findOrCreateUserByEmail(string $email, ?int $tenantId, ?string $name = null): User
    {
        $email = strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            return $user;
        }

        return User::query()->create([
            'name' => $name ?: (explode('@', $email)[0] ?: 'Patient'),
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'tenant_id' => $tenantId ?? TenantContext::id(),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function normalizeMobile(string $mobile): string
    {
        $normalized = preg_replace('/[^\d+]/', '', trim($mobile)) ?: '';
        if (strlen(preg_replace('/\D+/', '', $normalized) ?? '') < 8) {
            throw ValidationException::withMessages([
                'mobile' => ['Enter a valid mobile number.'],
            ]);
        }

        return $normalized;
    }
}
