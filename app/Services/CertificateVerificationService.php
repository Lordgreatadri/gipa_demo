<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\User;
use App\Notifications\CertificateVerificationAlert;
use App\Support\CertificatePermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateVerificationService
{
    public function __construct(private readonly CertificateIntegrityService $integrity) {}

    public function record(Certificate $certificate, User $officer, array $data, bool $notifyAuditors = true): CertificateVerification
    {
        Gate::forUser($officer)->authorize('verify', $certificate);

        $verification = DB::transaction(function () use ($certificate, $officer, $data): CertificateVerification {
            $existing = CertificateVerification::query()
                ->where('idempotency_key', $data['idempotency_key'])
                ->where('officer_id', $officer->id)
                ->first();
            if ($existing) {
                return $existing;
            }

            $certificate = Certificate::query()->lockForUpdate()->findOrFail($certificate->id);
            $result = $this->integrity->result($certificate);
            if ($data['officer_decision'] === CertificateVerification::DECISION_VALID
                && $result !== CertificateIntegrityService::RESULT_AUTHENTIC) {
                throw ValidationException::withMessages([
                    'officer_decision' => 'Only an authentic, active certificate may be recorded as valid.',
                ]);
            }

            $verification = $certificate->verifications()->create([
                'reference' => 'VER-'.now()->format('Y').'-'.Str::upper(Str::random(10)),
                'officer_id' => $officer->id,
                'district_id' => $certificate->district_id,
                'system_result' => $result,
                'officer_decision' => $data['officer_decision'],
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'accuracy_metres' => $data['accuracy_metres'] ?? null,
                'connectivity' => 'online',
                'registry_checked_at' => now(),
                'idempotency_key' => $data['idempotency_key'],
                'ip_address' => request()?->ip(),
                'user_agent' => Str::limit((string) request()?->userAgent(), 1000, ''),
                'created_at' => now(),
            ]);

            activity('certificates')
                ->causedBy($officer)
                ->performedOn($certificate)
                ->event('verified')
                ->withProperties([
                    'verification_uuid' => $verification->uuid,
                    'system_result' => $result,
                    'officer_decision' => $verification->officer_decision,
                    'district_id' => $verification->district_id,
                ])
                ->log('Certificate field verification recorded');

            return $verification;
        });

        if ($notifyAuditors
            && $verification->wasRecentlyCreated
            && ($verification->officer_decision === CertificateVerification::DECISION_SUSPICIOUS
                || $verification->system_result === CertificateIntegrityService::RESULT_SIGNATURE_INVALID)) {
            User::query()
                ->where('account_type', User::ACCOUNT_STAFF)
                ->where('status', User::STATUS_ACTIVE)
                ->whereKeyNot($officer->id)
                ->permission(CertificatePermissions::AUDIT_VIEW)
                ->eachById(fn (User $auditor) => Notification::send($auditor, new CertificateVerificationAlert($verification)));
        }

        return $verification;
    }
}
