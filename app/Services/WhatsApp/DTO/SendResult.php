<?php

namespace App\Services\WhatsApp\DTO;

readonly class SendResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function ok(?string $messageId = null, array $raw = []): self
    {
        return new self(success: true, messageId: $messageId, raw: $raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failed(string $error, array $raw = []): self
    {
        return new self(success: false, error: $error, raw: $raw);
    }
}
