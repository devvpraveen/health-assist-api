<?php

namespace App\Services\WhatsApp;

final class PhoneNormalizer
{
    public static function digits(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Extract phone digits from a WhatsApp remoteJid (e.g. 5511999999999@s.whatsapp.net).
     */
    public static function fromRemoteJid(?string $remoteJid): string
    {
        if ($remoteJid === null || $remoteJid === '') {
            return '';
        }

        $local = explode('@', $remoteJid)[0] ?? $remoteJid;

        // Group / LID style JIDs are not patient phone matches.
        if (str_contains($local, '-') || str_starts_with(strtolower($remoteJid), 'status@')) {
            return '';
        }

        return self::digits($local);
    }

    /**
     * Compare two phone strings by normalized digits (suffix match for country-code variance).
     */
    public static function matches(?string $a, ?string $b, int $minDigits = 10): bool
    {
        $da = self::digits($a);
        $db = self::digits($b);

        if ($da === '' || $db === '') {
            return false;
        }

        if ($da === $db) {
            return true;
        }

        $suffixLen = min(max($minDigits, 8), mb_strlen($da), mb_strlen($db));

        return mb_substr($da, -$suffixLen) === mb_substr($db, -$suffixLen);
    }
}
