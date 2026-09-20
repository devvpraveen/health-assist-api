<?php

namespace App\Services\Ocr;

use App\Contracts\Ocr\OcrProviderInterface;
use App\Services\Ocr\DTO\OcrResult;

class NullOcrProvider implements OcrProviderInterface
{
    public function extractText(string $path, string $mime, ?string $originalFilename = null): OcrResult
    {
        return new OcrResult(
            text: '',
            provider: 'null',
            pages: [],
            confidence: null,
        );
    }
}
