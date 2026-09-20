<?php

namespace App\Exceptions\Modules;

use Exception;

class EntitlementLimitExceededException extends Exception
{
    /**
     * @param  array{key: string, period_key: string, used: int, limit: int|null, remaining: int|null}  $snapshot
     */
    public function __construct(
        string $message,
        public readonly array $snapshot,
        int $code = 429,
    ) {
        parent::__construct($message, $code);
    }
}
