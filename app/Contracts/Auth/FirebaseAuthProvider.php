<?php

namespace App\Contracts\Auth;

/**
 * @phpstan-type FirebaseProfile array{uid: string, phone?: string|null, email?: string|null, name?: string|null}
 */
interface FirebaseAuthProvider
{
    /**
     * @return FirebaseProfile
     */
    public function verifyIdToken(string $idToken): array;
}
