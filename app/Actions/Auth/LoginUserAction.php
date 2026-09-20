<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @return array{user: User, token: string}
     */
    public function handle(string $email, string $password, string $deviceName = 'api'): array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => [__('Your account is not active.')],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($deviceName)->plainTextToken;

        $this->auditLogger->log('auth.login', $user, [
            'device_name' => $deviceName,
        ], $user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
