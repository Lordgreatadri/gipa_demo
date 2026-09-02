<?php

namespace App\Services;

use App\Models\Certificate;
use RuntimeException;

class CertificateIntegrityService
{
    public const RESULT_AUTHENTIC = 'authentic';

    public const RESULT_SIGNATURE_INVALID = 'signature_invalid';

    public const RESULT_EXPIRED = 'expired';

    public const RESULT_SUSPENDED = 'suspended';

    public const RESULT_REVOKED = 'revoked';

    public const RESULT_CANCELLED = 'cancelled';

    public const RESULT_SUPERSEDED = 'superseded';

    public function __construct(
        private readonly CertificatePayloadCanonicalizer $canonicalizer,
        private readonly CertificateSigner $signer,
    ) {}

    public function result(Certificate $certificate): string
    {
        if (! $this->hasValidSignature($certificate)) {
            return self::RESULT_SIGNATURE_INVALID;
        }

        return match (true) {
            $certificate->status === Certificate::STATUS_SUSPENDED => self::RESULT_SUSPENDED,
            $certificate->status === Certificate::STATUS_REVOKED => self::RESULT_REVOKED,
            $certificate->status === Certificate::STATUS_CANCELLED => self::RESULT_CANCELLED,
            $certificate->status === Certificate::STATUS_SUPERSEDED => self::RESULT_SUPERSEDED,
            $certificate->expires_at?->isPast() => self::RESULT_EXPIRED,
            $certificate->status === Certificate::STATUS_ACTIVE => self::RESULT_AUTHENTIC,
            default => self::RESULT_SIGNATURE_INVALID,
        };
    }

    public function hasValidSignature(Certificate $certificate): bool
    {
        if (! $certificate->signed_payload || ! $certificate->payload_hash || ! $certificate->digital_signature || ! $certificate->signing_key_id || ! $certificate->signature_algorithm) {
            return false;
        }

        try {
            $canonical = $this->canonicalizer->canonicalize($certificate->signed_payload);
            $hash = hash('sha256', $canonical);

            return hash_equals($certificate->payload_hash, $hash)
                && $this->signer->verify($canonical, $certificate->digital_signature, $certificate->signing_key_id, $certificate->signature_algorithm);
        } catch (RuntimeException) {
            return false;
        }
    }
}
