<?php

namespace App\Services\Billing;

class PaymentIntent
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $amountCents,
        public string $currency,
        public string $method,
        public ?string $gatewayReference = null,
        public array $meta = [],
    ) {}
}
