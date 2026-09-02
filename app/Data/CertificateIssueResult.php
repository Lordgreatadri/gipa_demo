<?php

namespace App\Data;

use App\Models\Certificate;

readonly class CertificateIssueResult
{
    public function __construct(
        public Certificate $certificate,
        public string $publicToken,
    ) {}
}
