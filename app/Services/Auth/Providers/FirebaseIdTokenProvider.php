<?php

namespace App\Services\Auth\Providers;

use App\Contracts\Auth\FirebaseAuthProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FirebaseIdTokenProvider implements FirebaseAuthProvider
{
    public function verifyIdToken(string $idToken): array
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw ValidationException::withMessages([
                'id_token' => ['Firebase identity token is required.'],
            ]);
        }

        $apiKey = trim((string) config('auth_providers.firebase.api_key'));
        if ($apiKey === '') {
            return $this->devDecode($idToken);
        }

        $response = Http::asJson()->post(
            'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key='.$apiKey,
            ['idToken' => $idToken],
        );

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'id_token' => ['Firebase identity token is invalid or expired.'],
            ]);
        }

        $user = $response->json('users.0');
        if (! is_array($user) || empty($user['localId'])) {
            throw ValidationException::withMessages([
                'id_token' => ['Firebase user not found for token.'],
            ]);
        }

        return [
            'uid' => (string) $user['localId'],
            'phone' => isset($user['phoneNumber']) ? (string) $user['phoneNumber'] : null,
            'email' => isset($user['email']) ? strtolower((string) $user['email']) : null,
            'name' => isset($user['displayName']) ? (string) $user['displayName'] : null,
        ];
    }

    /**
     * Local/dev fallback: accept opaque firebase stubs or JWT-shaped tokens.
     *
     * @return array{uid: string, phone?: string|null, email?: string|null, name?: string|null}
     */
    private function devDecode(string $idToken): array
    {
        if (! config('auth_providers.firebase.stub_allowed', true)) {
            throw ValidationException::withMessages([
                'id_token' => ['Firebase authentication is not configured.'],
            ]);
        }

        // firebase:+9198... or phone:+9198...
        if (preg_match('/^(?:firebase|phone):(\+?[\d\s\-()]+)$/i', $idToken, $m) === 1) {
            $phone = preg_replace('/[^\d+]/', '', $m[1]) ?: $m[1];

            return [
                'uid' => 'dev-'.md5($phone),
                'phone' => $phone,
                'email' => null,
                'name' => 'Patient',
            ];
        }

        $parts = explode('.', $idToken);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '', true);
            if (is_array($payload)) {
                $phone = isset($payload['phone_number']) ? (string) $payload['phone_number'] : null;
                $email = isset($payload['email']) ? strtolower((string) $payload['email']) : null;
                $uid = isset($payload['user_id'])
                    ? (string) $payload['user_id']
                    : (isset($payload['sub']) ? (string) $payload['sub'] : null);

                if ($uid && ($phone || $email)) {
                    return [
                        'uid' => $uid,
                        'phone' => $phone,
                        'email' => $email,
                        'name' => isset($payload['name']) ? (string) $payload['name'] : null,
                    ];
                }
            }
        }

        throw ValidationException::withMessages([
            'id_token' => ['Unable to verify Firebase identity token.'],
        ]);
    }
}
