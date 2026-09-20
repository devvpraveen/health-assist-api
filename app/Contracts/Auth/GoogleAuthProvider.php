<?php

namespace App\Contracts\Auth;

/**
 * @phpstan-type GoogleProfile array{email: string, name?: string|null, google_id?: string|null, picture?: string|null}
 */
interface GoogleAuthProvider
{
    /**
     * @return GoogleProfile
     */
    public function verifyIdToken(string $idToken): array;
}
