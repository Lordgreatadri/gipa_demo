<?php

namespace App\Policies;

use App\Models\InvestorDocument;
use App\Models\User;
use App\Support\InvestorPermissions;

class InvestorDocumentPolicy
{
    public function view(User $user, InvestorDocument $document): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $document->profile->user_id === $user->id
            || ($user->isStaff() && $user->can(InvestorPermissions::VIEW));
    }
}