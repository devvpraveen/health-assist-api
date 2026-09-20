<?php

namespace App\Contracts;

interface RecordIntegrityVerifierInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, proof_ref: string|null, provider: string|null}
     */
    public function attest(string $recordType, string $recordId, array $payload): array;

    /**
     * @return array{valid: bool, status: string, provider: string|null}
     */
    public function verify(string $recordType, string $recordId, ?string $proofRef = null): array;

    /**
     * @return array{enabled: bool, provider: string|null, status: string}
     */
    public function getStatus(): array;
}
