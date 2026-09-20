<?php

namespace App\Services\Ocr;

use App\Contracts\Ocr\OcrProviderInterface;
use App\Services\Ocr\DTO\OcrResult;

/**
 * Deterministic OCR for local/testing. Returns sample lab values when the
 * filename/hash does not embed emergency keywords.
 */
class MockOcrProvider implements OcrProviderInterface
{
    public function extractText(string $path, string $mime, ?string $originalFilename = null): OcrResult
    {
        $basename = basename($path);
        $nameHint = mb_strtolower(($originalFilename ?? '').'|'.$basename);
        $seed = hash('sha256', $basename.'|'.$mime.'|'.($originalFilename ?? ''));

        $fileSnippet = '';
        if (is_readable($path)) {
            $fileSnippet = mb_strtolower((string) @file_get_contents($path, false, null, 0, 2048));
        }

        $haystack = $nameHint.'|'.$fileSnippet;

        if (str_contains($haystack, 'chest_pain')
            || str_contains($haystack, 'chest pain')
            || str_contains($haystack, 'emergency')) {
            $text = implode("\n", [
                'LABORATORY REPORT',
                'Patient presents with chest pain and shortness of breath.',
                'Hb: 13.2 g/dL',
                'WBC: 8.1 x10^3/uL',
                'Glucose: 92 mg/dL',
                'Notes: Seek emergency care if symptoms worsen.',
            ]);

            return new OcrResult(
                text: $text,
                provider: 'mock',
                pages: [['page' => 1, 'text' => $text]],
                confidence: 0.97,
            );
        }

        $text = implode("\n", [
            'LABORATORY REPORT',
            'Sample ID: MOCK-'.strtoupper(substr($seed, 0, 8)),
            'Hb: 11.2 g/dL',
            'WBC: 12.5 x10^3/uL',
            'Glucose: 110 mg/dL',
            'Comment: Routine outpatient labs.',
        ]);

        return new OcrResult(
            text: $text,
            provider: 'mock',
            pages: [['page' => 1, 'text' => $text]],
            confidence: 0.95,
        );
    }
}
