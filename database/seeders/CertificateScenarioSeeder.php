<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\CertificateVerification;
use App\Models\InvestorProfile;
use App\Models\Opportunity;
use App\Models\User;
use App\Notifications\CertificateVerificationAlert;
use App\Services\CertificateIntegrityService;
use App\Services\CertificateVerificationService;
use App\Services\CertificateWorkflowService;
use App\Support\CertificatePermissions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class CertificateScenarioSeeder extends Seeder
{
    public function __construct(
        private readonly CertificateWorkflowService $workflow,
        private readonly CertificateVerificationService $verification,
        private readonly CertificateIntegrityService $integrity,
    ) {}

    public function run(): void
    {
        $count = (int) config('iomp.demo_users.investor_count');
        $pattern = (string) config('iomp.demo_users.investor_email_pattern');
        $profiles = $this->profiles($pattern, $count);
        $opportunities = Opportunity::query()
            ->select('id', 'district_id', 'title', 'latitude', 'longitude')
            ->orderBy('id')
            ->get();
        $type = CertificateType::query()->where('code', 'INVESTMENT_REGISTRATION')->first();
        $issuer = $this->demoStaff('Reviewer / Approver');
        $officer = $this->demoStaff('District Officer');
        $assigner = $this->demoStaff('Super Administrator');

        if ($count < 1 || $profiles->count() !== $count || $opportunities->isEmpty() || ! $type) {
            throw ValidationException::withMessages([
                'certificate_scenarios' => 'Seed demo investors, opportunities and certificate types before certificate scenarios.',
            ]);
        }

        foreach (range(1, $count) as $index) {
            $profile = $profiles->get(sprintf($pattern, $index));
            $opportunity = $opportunities[($index - 1) % $opportunities->count()];
            $certificate = Certificate::query()->firstOrNew([
                'certificate_number' => sprintf('GIPA-DEMO-CERT-%04d', $index),
            ]);

            if (! $certificate->exists || $certificate->status === Certificate::STATUS_DRAFT) {
                $certificate->fill([
                    'certificate_type_id' => $type->id,
                    'investor_profile_id' => $profile->id,
                    'opportunity_id' => $opportunity->id,
                    'district_id' => $opportunity->district_id,
                    'holder_name_snapshot' => $profile->display_name,
                    'organization_name_snapshot' => $profile->user->organization,
                    'project_name_snapshot' => $opportunity->title,
                    'expires_at' => $this->expiryFor($index),
                    'created_by' => $issuer->id,
                    'updated_by' => $issuer->id,
                ])->save();
            }

            $officer->districtAssignments()->firstOrCreate([
                'district_id' => $certificate->district_id,
                'starts_at' => CarbonImmutable::create(2020, 1, 1, 0, 0, 0, 'UTC'),
            ], [
                'assigned_by' => $assigner->id,
                'is_primary' => false,
            ]);

            if ($index % 25 === 0) {
                continue;
            }

            if ($certificate->status === Certificate::STATUS_DRAFT) {
                $certificate = $this->workflow->issue($certificate, $issuer)->certificate;
            }

            if ($index % 17 === 0 && in_array($certificate->status, [Certificate::STATUS_ACTIVE, Certificate::STATUS_SUSPENDED], true)) {
                $certificate = $this->workflow->revoke($certificate, $issuer, 'Demo compliance revocation scenario.');
            } elseif ($index % 11 === 0 && $certificate->status === Certificate::STATUS_ACTIVE) {
                $certificate = $this->workflow->suspend($certificate, $issuer, 'Demo review hold scenario.');
            }

            if ($index % 3 === 0) {
                $result = $this->integrity->result($certificate);
                $this->verification->record($certificate, $officer, [
                    'idempotency_key' => sprintf('demo-certificate-verification-%04d', $index),
                    'officer_decision' => $index % 10 === 0
                        ? CertificateVerification::DECISION_SUSPICIOUS
                        : ($result === CertificateIntegrityService::RESULT_AUTHENTIC
                            ? CertificateVerification::DECISION_VALID
                            : CertificateVerification::DECISION_INVALID),
                    'notes' => $index % 10 === 0
                        ? 'Demo inspection flagged for secondary review.'
                        : 'Demo field inspection matched the online registry result.',
                    'latitude' => $opportunity->latitude,
                    'longitude' => $opportunity->longitude,
                    'accuracy_metres' => 12 + ($index % 18),
                ], notifyAuditors: false);
            }
        }

        $this->seedAuditorNotifications($officer);
    }

    private function profiles(string $pattern, int $count): Collection
    {
        $emails = collect(range(1, max(1, $count)))->map(fn (int $index) => sprintf($pattern, $index));

        return InvestorProfile::query()
            ->select('id', 'user_id', 'display_name')
            ->with('user:id,email,organization')
            ->whereHas('user', fn ($query) => $query->whereIn('email', $emails))
            ->get()
            ->keyBy('user.email');
    }

    private function demoStaff(string $role): User
    {
        $account = collect(config('iomp.demo_users.roles'))->firstWhere('role', $role);
        $user = $account ? User::query()->where('email', $account['email'])->first() : null;

        if (! $user || ! $user->hasRole($role)) {
            throw ValidationException::withMessages([
                'certificate_scenarios' => "The configured {$role} demo staff account must be seeded first.",
            ]);
        }

        return $user;
    }

    private function seedAuditorNotifications(User $officer): void
    {
        $auditors = User::query()
            ->where('account_type', User::ACCOUNT_STAFF)
            ->where('status', User::STATUS_ACTIVE)
            ->whereKeyNot($officer->id)
            ->permission(CertificatePermissions::AUDIT_VIEW)
            ->get(['id']);
        $namespace = '6b9b8d5e-392f-54ca-9078-fc4ef8f9d88a';

        CertificateVerification::query()
            ->where('idempotency_key', 'like', 'demo-certificate-verification-%')
            ->where('officer_decision', CertificateVerification::DECISION_SUSPICIOUS)
            ->with('certificate:id,uuid,certificate_number')
            ->eachById(function (CertificateVerification $verification) use ($auditors, $namespace): void {
                foreach ($auditors as $auditor) {
                    $notification = new CertificateVerificationAlert($verification);
                    $timestamp = now();

                    DB::table('notifications')->insertOrIgnore([
                        'id' => Uuid::uuid5($namespace, "{$verification->uuid}:{$auditor->id}")->toString(),
                        'type' => CertificateVerificationAlert::class,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $auditor->id,
                        'data' => json_encode($notification->toArray($auditor), JSON_THROW_ON_ERROR),
                        'read_at' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            });
    }

    private function expiryFor(int $index): CarbonImmutable
    {
        $year = CarbonImmutable::now()->startOfYear();

        return match ($index % 10) {
            1 => $year->addMonth()->setDay(15),
            2 => $year->addMonths(4)->setDay(15),
            3 => $year->addMonths(7)->setDay(15),
            4 => $year->addMonths(10)->setDay(15),
            5 => CarbonImmutable::now()->addDays(($index % 25) + 1),
            default => CarbonImmutable::now()->addMonths(6 + ($index % 18)),
        };
    }
}
