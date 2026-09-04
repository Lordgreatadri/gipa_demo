<?php

namespace App\Services\Assistant\Tools;

use App\Services\Assistant\Data\ToolResult;

/**
 * Explains how to authenticate a GIPA certificate. Verification itself is only
 * performed through the signed public verification link / QR code printed on
 * the certificate (see CertificateVerificationController), which enforces the
 * 64-character bearer token and the integrity check. This tool therefore
 * returns guidance only and never exposes registry data keyed by number.
 */
class CertificateVerificationTool extends AbstractTool
{
    public function name(): string
    {
        return 'certificate_verification';
    }

    protected function triggers(): array
    {
        return ['certificate', 'verify', 'authentic', 'genuine', 'is it real', 'is it valid', 'validity'];
    }

    public function handle(string $question): ?ToolResult
    {
        return new ToolResult(
            tool: $this->name(),
            summary: 'Every GIPA certificate carries a unique QR code and a verification link printed on the '
                .'document itself. To confirm a certificate is genuine, scan the QR code or open that verification '
                .'link — it resolves to the official platform and, after validating the certificate\'s secure token, '
                .'displays the current status, holder and issue details. Always rely on that link rather than a '
                .'certificate number, and contact GIPA directly if the link does not resolve.',
            sourceLabel: 'Certificate verification guidance',
            reference: null,
        );
    }
}
