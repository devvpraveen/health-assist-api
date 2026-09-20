<?php

namespace App\Services\Integrity;

class IntegrityHasher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function hash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
