<?php

namespace App\Policies;

use App\Models\CertificateVerification;
use App\Models\User;
use App\Support\CertificatePermissions;

class CertificateVerificationPolicy
{
    public function viewEvidence(User $user, CertificateVerification $verification): bool
    {
        return $user->can(CertificatePermissions::EVIDENCE_VIEW)
            && $user->can('view', $verification->certificate);
    }
}
