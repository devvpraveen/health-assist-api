<?php

namespace App\Services\Billing;

class PaymentResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $gatewayReference = null,
        public ?string $message = null,
        public array $meta = [],
    ) {}

    public static function succeeded(?string $gatewayReference = null, array $meta = []): self
    {
        return new self(
            success: true,
            status: 'completed',
            gatewayReference: $gatewayReference,
            meta: $meta,
        );
    }

    public static function failed(string $message, ?string $status = 'failed'): self
    {
        return new self(
            success: false,
            status: $status ?? 'failed',
            message: $message,
        );
    }
}
