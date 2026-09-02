<?php

namespace App\Services;

use App\Models\Certificate;
use JsonException;

class CertificatePayloadCanonicalizer
{
    public const VERSION = 1;

    public function payload(Certificate $certificate): array
    {
        $certificate->loadMissing(['type:id,code', 'district:id,uuid,code,name', 'opportunity:id,uuid']);

        return [
            'schema_version' => self::VERSION,
            'certificate_uuid' => $certificate->uuid,
            'certificate_number' => $certificate->certificate_number,
            'certificate_type_code' => $certificate->type->code,
            'holder_name' => $certificate->holder_name_snapshot,
            'organization_name' => $certificate->organization_name_snapshot,
            'opportunity_uuid' => $certificate->opportunity?->uuid,
            'project_name' => $certificate->project_name_snapshot,
            'district' => [
                'uuid' => $certificate->district->uuid,
                'code' => $certificate->district->code,
                'name' => $certificate->district->name,
            ],
            'issued_at' => $certificate->issued_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'expires_at' => $certificate->expires_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /** @throws JsonException */
    public function canonicalize(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}
