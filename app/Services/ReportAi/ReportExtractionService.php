<?php

namespace App\Services\ReportAi;

class ReportExtractionService
{
    /**
     * Heuristically parse OCR text into structured extracted_facts and
     * reference_range_findings. Does not call an LLM.
     *
     * @return array{extracted_facts: array{tests: list<array{name: string, value: float|string, unit: string|null, flag: string|null}>}, reference_range_findings: list<array{name: string, value: float, unit: string|null, flag: string, low: float|null, high: float|null}>}
     */
    public function extract(string $ocrText): array
    {
        $tests = [];
        $findings = [];
        $ranges = config('report_ai.reference_ranges', []);

        $lines = preg_split('/\R+/', $ocrText) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^([A-Za-z][A-Za-z0-9\s\/\-]{0,40}?)\s*[:=]\s*([-+]?\d+(?:\.\d+)?)\s*([A-Za-z%\/\^\d]+(?:\/[A-Za-z]+)?)?/u', $line, $match)) {
                continue;
            }

            $rawName = trim($match[1]);
            $value = (float) $match[2];
            $unit = isset($match[3]) ? trim($match[3]) : null;
            $canonical = $this->resolveAnalyte($rawName, $ranges);

            $flag = null;
            $rangeMeta = null;

            if ($canonical !== null && isset($ranges[$canonical])) {
                $range = $ranges[$canonical];
                $low = (float) ($range['low'] ?? 0);
                $high = (float) ($range['high'] ?? 0);
                $flag = $value < $low ? 'low' : ($value > $high ? 'high' : 'normal');
                $unit = $unit ?: ($range['unit'] ?? null);
                $rangeMeta = [
                    'name' => $canonical,
                    'value' => $value,
                    'unit' => $unit,
                    'flag' => $flag,
                    'low' => $low,
                    'high' => $high,
                ];
            }

            $tests[] = [
                'name' => $canonical ?? $rawName,
                'value' => $value,
                'unit' => $unit,
                'flag' => $flag,
            ];

            if ($rangeMeta !== null) {
                $findings[] = $rangeMeta;
            }
        }

        return [
            'extracted_facts' => [
                'tests' => $tests,
                'source' => 'heuristic_ocr',
            ],
            'reference_range_findings' => $findings,
        ];
    }

    /**
     * @param  array<string, array{aliases?: list<string>}>  $ranges
     */
    private function resolveAnalyte(string $rawName, array $ranges): ?string
    {
        $normalized = mb_strtolower(trim($rawName));

        foreach ($ranges as $key => $config) {
            $aliases = array_map('mb_strtolower', $config['aliases'] ?? [$key]);
            if (in_array($normalized, $aliases, true) || $normalized === mb_strtolower($key)) {
                return $key;
            }
        }

        return null;
    }
}
