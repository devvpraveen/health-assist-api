<?php

namespace App\Services\Auth\Providers;

use App\Contracts\Auth\GoogleAuthProvider;
use Illuminate\Validation\ValidationException;

class DevGoogleAuthProvider implements GoogleAuthProvider
{
    public function verifyIdToken(string $idToken): array
    {
        if (! config('auth_providers.google.stub_allowed', true)) {
            throw ValidationException::withMessages([
                'id_token' => ['Google authentication is not configured.'],
            ]);
        }

        $idToken = trim($idToken);
        if ($idToken === '') {
            throw ValidationException::withMessages([
                'id_token' => ['Google identity token is required.'],
            ]);
        }

        // Dev stub: treat token as email, or "Name <email>", or bare local-part.
        if (str_contains($idToken, '@')) {
            if (preg_match('/^(.+)<([^>]+)>$/', $idToken, $matches) === 1) {
                return [
                    'email' => strtolower(trim($matches[2])),
                    'name' => trim($matches[1]) ?: null,
                    'google_id' => 'dev-'.md5(strtolower(trim($matches[2]))),
                ];
            }

            return [
                'email' => strtolower($idToken),
                'name' => explode('@', $idToken)[0],
                'google_id' => 'dev-'.md5(strtolower($idToken)),
            ];
        }

        $email = strtolower($idToken).'@gmail.dev';

        return [
            'email' => $email,
            'name' => $idToken,
            'google_id' => 'dev-'.md5($email),
        ];
    }
}
