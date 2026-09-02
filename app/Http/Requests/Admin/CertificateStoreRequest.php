<?php

namespace App\Http\Requests\Admin;

use App\Models\CertificateType;
use App\Models\District;
use App\Models\Opportunity;
use App\Support\CertificatePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CertificateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true && $this->user()->can(CertificatePermissions::ISSUE);
    }

    public function rules(): array
    {
        $districtId = District::query()->where('uuid', $this->input('district'))->value('id');

        return [
            'certificate_type' => ['required', 'uuid', Rule::exists('certificate_types', 'uuid')->where('is_active', true)],
            'district' => ['required', 'uuid', Rule::exists('districts', 'uuid')->whereNull('deleted_at')],
            'opportunity' => ['nullable', 'uuid', Rule::exists('opportunities', 'uuid')->where(fn ($query) => $query->whereNull('deleted_at')->where('district_id', $districtId))],
            'holder_name' => ['required', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function certificateData(): array
    {
        return [
            'certificate_type_id' => CertificateType::query()->where('uuid', $this->validated('certificate_type'))->value('id'),
            'district_id' => District::query()->where('uuid', $this->validated('district'))->value('id'),
            'opportunity_id' => $this->validated('opportunity')
                ? Opportunity::query()->where('uuid', $this->validated('opportunity'))->value('id')
                : null,
            'holder_name_snapshot' => $this->validated('holder_name'),
            'organization_name_snapshot' => $this->validated('organization_name'),
            'project_name_snapshot' => $this->validated('project_name'),
            'expires_at' => $this->validated('expires_at'),
        ];
    }
}
