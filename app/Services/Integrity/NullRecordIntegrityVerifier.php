<?php

namespace App\Services\Integrity;

use App\Contracts\RecordIntegrityVerifierInterface;

class NullRecordIntegrityVerifier implements RecordIntegrityVerifierInterface
{
    /**
     * {@inheritdoc}
     */
    public function attest(string $recordType, string $recordId, array $payload): array
    {
        return [
            'status' => 'disabled',
            'proof_ref' => null,
            'provider' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $recordType, string $recordId, ?string $proofRef = null): array
    {
        return [
            'valid' => false,
            'status' => 'disabled',
            'provider' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): array
    {
        return [
            'enabled' => false,
            'provider' => null,
            'status' => 'disabled',
        ];
    }
}
