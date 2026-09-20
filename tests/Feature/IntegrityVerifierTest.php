<?php

namespace Tests\Feature;

use App\Contracts\RecordIntegrityVerifierInterface;
use App\Services\Integrity\NullRecordIntegrityVerifier;
use Tests\TestCase;

class IntegrityVerifierTest extends TestCase
{
    public function test_null_verifier_returns_disabled_status(): void
    {
        $verifier = $this->app->make(RecordIntegrityVerifierInterface::class);

        $this->assertInstanceOf(NullRecordIntegrityVerifier::class, $verifier);

        $status = $verifier->getStatus();

        $this->assertFalse($status['enabled']);
        $this->assertSame('disabled', $status['status']);
        $this->assertNull($status['provider']);

        $attest = $verifier->attest('record', '1', ['foo' => 'bar']);
        $this->assertSame('disabled', $attest['status']);
        $this->assertNull($attest['proof_ref']);

        $verify = $verifier->verify('record', '1');
        $this->assertFalse($verify['valid']);
        $this->assertSame('disabled', $verify['status']);
    }

    public function test_blockchain_verification_config_defaults_to_false(): void
    {
        $this->assertFalse(config('integrity.blockchain_verification_enabled'));
        $this->assertNull(config('integrity.blockchain_provider'));
    }
}
