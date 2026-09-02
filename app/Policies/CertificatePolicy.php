<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use App\Support\CertificatePermissions;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->isActive()
            && $user->isStaff()
            && $user->can(CertificatePermissions::VIEW)
            && Certificate::query()->accessibleTo($user)->whereKey($certificate->id)->exists();
    }

    public function verify(User $user, Certificate $certificate): bool
    {
        return $user->can(CertificatePermissions::VERIFY) && $this->view($user, $certificate);
    }
}
