<?php

namespace App\Services\Billing;

class RefundIntent
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $amountCents,
        public string $currency,
        public ?string $gatewayReference = null,
        public ?string $reason = null,
        public array $meta = [],
    ) {}
}
