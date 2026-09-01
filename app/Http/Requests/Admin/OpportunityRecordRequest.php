<?php

namespace App\Http\Requests\Admin;

use App\Models\Opportunity;
use App\Models\Sector;
use App\Support\WorkflowPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $this->user()?->can(WorkflowPermissions::OPPORTUNITY_SUBMIT)
            && (! $opportunity || $opportunity->workflow_status === Opportunity::WORKFLOW_DRAFT);
    }

    public function rules(): array
    {
        $sectorId = Sector::query()->where('uuid', $this->input('sector'))->value('id');

        return [
            'district' => ['required', 'uuid', Rule::exists('districts', 'uuid')->whereNull('deleted_at')],
            'sector' => ['required', 'uuid', Rule::exists('sectors', 'uuid')->where('is_active', true)],
            'sub_sector' => [
                'nullable',
                'uuid',
                Rule::exists('sub_sectors', 'uuid')->where(fn ($query) => $query
                    ->where('sector_id', $sectorId)
                    ->where('is_active', true)),
            ],
            'enterprise_type' => ['required', 'uuid', Rule::exists('enterprise_types', 'uuid')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'overview' => ['required', 'string', 'max:20000'],
            'objectives' => ['nullable', 'string', 'max:20000'],
            'rationale' => ['nullable', 'string', 'max:20000'],
            'success_factors' => ['nullable', 'string', 'max:20000'],
            'competitive_advantages' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
