<?php

namespace App\Contracts\Ocr;

use App\Services\Ocr\DTO\OcrResult;

interface OcrProviderInterface
{
    public function extractText(string $path, string $mime, ?string $originalFilename = null): OcrResult;
}
