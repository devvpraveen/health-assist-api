<?php

namespace App\Exceptions\AI;

use Exception;

class AiQuotaExceededException extends Exception
{
    /**
     * @param  array{period: string, requests_used: int, requests_limit: int|null, tokens_used: int, tokens_limit: int|null, reason: string}  $snapshot
     */
    public function __construct(
        string $message,
        public readonly array $snapshot,
        int $code = 429,
    ) {
        parent::__construct($message, $code);
    }
}
