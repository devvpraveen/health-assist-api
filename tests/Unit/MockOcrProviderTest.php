<?php

namespace Tests\Unit;

use App\Services\Ocr\MockOcrProvider;
use App\Services\Ocr\NullOcrProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MockOcrProviderTest extends TestCase
{
    #[Test]
    public function mock_ocr_returns_deterministic_lab_values(): void
    {
        $provider = new MockOcrProvider;
        $path = sys_get_temp_dir().'/lab_report_'.uniqid().'.pdf';
        file_put_contents($path, '%PDF-mock');

        $first = $provider->extractText($path, 'application/pdf', 'lab_report.pdf');
        $second = $provider->extractText($path, 'application/pdf', 'lab_report.pdf');

        $this->assertSame('mock', $first->provider);
        $this->assertSame($first->text, $second->text);
        $this->assertStringContainsString('Hb:', $first->text);
        $this->assertStringContainsString('WBC:', $first->text);
        $this->assertStringContainsString('Glucose:', $first->text);
        $this->assertNotNull($first->confidence);

        @unlink($path);
    }

    #[Test]
    public function mock_ocr_returns_emergency_sample_for_chest_pain_filename(): void
    {
        $provider = new MockOcrProvider;
        $path = sys_get_temp_dir().'/report_'.uniqid().'.pdf';
        file_put_contents($path, '%PDF-mock');

        $result = $provider->extractText($path, 'application/pdf', 'chest_pain_lab.pdf');

        $this->assertStringContainsString('chest pain', mb_strtolower($result->text));
        $this->assertStringContainsString('Hb:', $result->text);

        @unlink($path);
    }

    #[Test]
    public function null_ocr_returns_empty_text(): void
    {
        $result = (new NullOcrProvider)->extractText('/tmp/none.pdf', 'application/pdf');

        $this->assertSame('null', $result->provider);
        $this->assertSame('', $result->text);
    }
}
