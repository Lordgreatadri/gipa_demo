<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificateIntegrityService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(string $token, CertificateIntegrityService $integrity): View
    {
        $certificate = $this->find($token);

        return view('public.certificates.verify', [
            'certificate' => $certificate,
            'result' => $certificate ? $integrity->result($certificate) : 'not_found',
            'checkedAt' => now(),
        ]);
    }

    public function api(string $token, CertificateIntegrityService $integrity): JsonResponse
    {
        $certificate = $this->find($token);
        if (! $certificate) {
            return response()->json(['data' => ['result' => 'not_found', 'checked_at' => now()->toIso8601String()]], 404);
        }

        return response()->json(['data' => [
            'result' => $integrity->result($certificate),
            'certificate_number' => $certificate->certificate_number,
            'certificate_type' => $certificate->type->name,
            'holder_name' => $certificate->holder_name_snapshot,
            'organization_name' => $certificate->organization_name_snapshot,
            'project_name' => $certificate->project_name_snapshot,
            'district' => $certificate->district->name,
            'issued_at' => $certificate->issued_at?->toDateString(),
            'expires_at' => $certificate->expires_at?->toDateString(),
            'checked_at' => now()->toIso8601String(),
        ]]);
    }

    private function find(string $token): ?Certificate
    {
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return null;
        }

        return Certificate::query()
            ->select(['id', 'uuid', 'certificate_number', 'certificate_type_id', 'district_id', 'status', 'holder_name_snapshot', 'organization_name_snapshot', 'project_name_snapshot', 'issued_at', 'expires_at', 'canonicalization_version', 'signed_payload', 'payload_hash', 'signature_algorithm', 'signing_key_id', 'digital_signature'])
            ->with(['type:id,name', 'district:id,name'])
            ->where('public_token_hash', hash('sha256', $token))
            ->first();
    }
}
