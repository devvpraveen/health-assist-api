<?php

namespace App\Services\Ocr\DTO;

readonly class OcrResult
{
    /**
     * @param  list<array{page?: int, text?: string}>|null  $pages
     */
    public function __construct(
        public string $text,
        public string $provider,
        public ?array $pages = null,
        public ?float $confidence = null,
    ) {}
}
