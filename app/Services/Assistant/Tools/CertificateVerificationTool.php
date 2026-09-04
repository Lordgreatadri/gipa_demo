<?php

namespace App\Services\Assistant\Tools;

use App\Models\Certificate;
use App\Services\Assistant\Data\ToolResult;

/**
 * Explains how to authenticate a GIPA certificate and, when a certificate
 * number is supplied, reports its public status. Only non-sensitive fields that
 * already appear on the issued certificate are returned.
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
        $guidance = 'Every GIPA certificate carries a unique QR code and verification link. '
            .'To confirm a certificate is genuine, scan the QR code or open the verification link '
            .'printed on the certificate — it resolves to the official platform and shows the current status, '
            .'holder and issue details.';

        $number = $this->extractCertificateNumber($question);

        if ($number !== null) {
            $certificate = Certificate::query()
                ->where('certificate_number', $number)
                ->where('status', '!=', Certificate::STATUS_DRAFT)
                ->with(['certificateType:id,name', 'district:id,name'])
                ->first();

            if ($certificate !== null) {
                $details = [];
                $details[] = "Certificate {$certificate->certificate_number} is currently {$certificate->status}.";
                if ($certificate->certificateType) {
                    $details[] = "Type: {$certificate->certificateType->name}.";
                }
                if ($certificate->holder_name_snapshot) {
                    $details[] = "Holder: {$certificate->holder_name_snapshot}.";
                }
                if ($certificate->district) {
                    $details[] = "District: {$certificate->district->name}.";
                }
                if ($certificate->issued_at) {
                    $details[] = 'Issued: '.$certificate->issued_at->toFormattedDateString().'.';
                }
                if ($certificate->expires_at) {
                    $details[] = 'Expires: '.$certificate->expires_at->toFormattedDateString().'.';
                }

                return new ToolResult(
                    tool: $this->name(),
                    summary: implode(' ', $details)."\n\n".$guidance,
                    sourceLabel: 'Certificate registry',
                    reference: null,
                    data: ['certificate_number' => $certificate->certificate_number, 'status' => $certificate->status],
                );
            }

            return new ToolResult(
                tool: $this->name(),
                summary: "I couldn't find a certificate with the number \"{$number}\" on the platform. "
                    .'Please double-check the number. '.$guidance,
                sourceLabel: 'Certificate registry',
                reference: null,
                data: ['certificate_number' => $number, 'status' => 'not_found'],
            );
        }

        return new ToolResult(
            tool: $this->name(),
            summary: $guidance.' If you share the certificate number I can check its status for you.',
            sourceLabel: 'Certificate verification guidance',
            reference: null,
        );
    }

    private function extractCertificateNumber(string $question): ?string
    {
        if (preg_match('/\b([A-Z]{2,}[-\/][A-Z0-9\-\/]{3,})\b/i', $question, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
