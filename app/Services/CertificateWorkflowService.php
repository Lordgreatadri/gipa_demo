<?php

namespace App\Services;

use App\Data\CertificateIssueResult;
use App\Jobs\GenerateCertificateArtifacts;
use App\Models\Certificate;
use App\Models\User;
use App\Support\CertificatePermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateWorkflowService
{
    public function __construct(
        private readonly CertificatePayloadCanonicalizer $canonicalizer,
        private readonly CertificateSigner $signer,
    ) {}

    public function issue(Certificate $certificate, User $actor): CertificateIssueResult
    {
        Gate::forUser($actor)->authorize(CertificatePermissions::ISSUE);

        return DB::transaction(function () use ($certificate, $actor): CertificateIssueResult {
            $certificate = Certificate::query()->lockForUpdate()->findOrFail($certificate->id);
            $this->assertStatus($certificate, Certificate::STATUS_DRAFT, 'issue');

            $token = Str::random(64);
            $issuedAt = now();
            $before = $certificate->only(['status', 'issued_at', 'issued_by', 'version']);
            $certificate->forceFill([
                'issued_at' => $issuedAt,
                'expires_at' => $certificate->expires_at ?? $this->defaultExpiry($certificate, $issuedAt),
                'issued_by' => $actor->id,
                'canonicalization_version' => CertificatePayloadCanonicalizer::VERSION,
            ]);
            $payload = $this->canonicalizer->payload($certificate);
            $canonical = $this->canonicalizer->canonicalize($payload);
            $signature = $this->signer->sign($canonical);

            $certificate->forceFill([
                'public_token_hash' => hash('sha256', $token),
                'public_token' => $token,
                'status' => Certificate::STATUS_ACTIVE,
                'signed_payload' => $payload,
                'payload_hash' => hash('sha256', $canonical),
                'signature_algorithm' => $signature['algorithm'],
                'signing_key_id' => $signature['key_id'],
                'digital_signature' => $signature['signature'],
                'updated_by' => $actor->id,
                'version' => $certificate->version + 1,
            ])->save();

            $event = $this->event($certificate, $actor, 'issued', Certificate::STATUS_DRAFT, Certificate::STATUS_ACTIVE);
            $this->audit($certificate, $actor, 'issued', $before, $certificate->only(array_keys($before)), $event);
            GenerateCertificateArtifacts::dispatch($certificate->id)->afterCommit();

            return new CertificateIssueResult($certificate->fresh(), $token);
        });
    }

    public function suspend(Certificate $certificate, User $actor, string $reason): Certificate
    {
        Gate::forUser($actor)->authorize(CertificatePermissions::SUSPEND);

        return $this->transition($certificate, $actor, 'suspended', Certificate::STATUS_ACTIVE, Certificate::STATUS_SUSPENDED, $reason);
    }

    public function reinstate(Certificate $certificate, User $actor, string $reason): Certificate
    {
        Gate::forUser($actor)->authorize(CertificatePermissions::SUSPEND);

        return $this->transition($certificate, $actor, 'reinstated', Certificate::STATUS_SUSPENDED, Certificate::STATUS_ACTIVE, $reason);
    }

    public function revoke(Certificate $certificate, User $actor, string $reason): Certificate
    {
        Gate::forUser($actor)->authorize(CertificatePermissions::REVOKE);

        return $this->transition($certificate, $actor, 'revoked', $certificate->status, Certificate::STATUS_REVOKED, $reason, [Certificate::STATUS_ACTIVE, Certificate::STATUS_SUSPENDED]);
    }

    private function transition(Certificate $certificate, User $actor, string $action, string $expected, string $next, string $reason, ?array $allowed = null): Certificate
    {
        return DB::transaction(function () use ($certificate, $actor, $action, $expected, $next, $reason, $allowed): Certificate {
            $certificate = Certificate::query()->lockForUpdate()->findOrFail($certificate->id);
            if ($allowed ? ! in_array($certificate->status, $allowed, true) : $certificate->status !== $expected) {
                throw ValidationException::withMessages(['workflow' => "Certificate cannot be {$action} from {$certificate->status}."]);
            }

            $from = $certificate->status;
            $before = $certificate->only(['status', 'version']);
            $certificate->forceFill([
                'status' => $next,
                'updated_by' => $actor->id,
                'version' => $certificate->version + 1,
            ])->save();
            $event = $this->event($certificate, $actor, $action, $from, $next, $reason);
            $this->audit($certificate, $actor, $action, $before, $certificate->only(array_keys($before)), $event);

            return $certificate->fresh();
        });
    }

    private function defaultExpiry(Certificate $certificate, \DateTimeInterface $issuedAt): ?\DateTimeInterface
    {
        $months = $certificate->type()->value('default_validity_months');

        return $months ? now()->setTimestamp($issuedAt->getTimestamp())->addMonthsNoOverflow($months) : null;
    }

    private function assertStatus(Certificate $certificate, string $status, string $action): void
    {
        if ($certificate->status !== $status) {
            throw ValidationException::withMessages(['workflow' => "Certificate must be {$status} to {$action}."]);
        }
    }

    private function event(Certificate $certificate, User $actor, string $action, ?string $from, string $to, ?string $reason = null): Model
    {
        return $certificate->lifecycleEvents()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'metadata' => ['schema_version' => 1],
        ]);
    }

    private function audit(Certificate $certificate, User $actor, string $action, array $before, array $after, Model $event): void
    {
        activity('certificates')
            ->causedBy($actor)
            ->performedOn($certificate)
            ->event($action)
            ->withProperties(['before' => $before, 'after' => $after, 'lifecycle_event_uuid' => $event->uuid])
            ->log("Certificate {$action}");
    }
}
