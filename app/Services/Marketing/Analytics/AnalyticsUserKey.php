<?php

namespace App\Services\Marketing\Analytics;

use App\Models\User;

final class AnalyticsUserKey
{
    public static function fromUser(User $user): string
    {
        $salt = (string) config('marketing.analytics.user_key_salt', 'healthassist-marketing');

        return hash('sha256', $salt.'|'.$user->uuid);
    }

    public static function fromUuid(string $uuid): string
    {
        $salt = (string) config('marketing.analytics.user_key_salt', 'healthassist-marketing');

        return hash('sha256', $salt.'|'.$uuid);
    }
}
