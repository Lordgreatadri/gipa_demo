<?php

namespace App\Http\Requests\Admin;

use App\Models\District;
use App\Models\Region;
use App\Support\WorkflowPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistrictRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $district = $this->route('district');

        return $this->user()?->can(WorkflowPermissions::DISTRICT_SUBMIT)
            && (! $district || $district->workflow_status === District::STATUS_DRAFT);
    }

    public function rules(): array
    {
        $district = $this->route('district');
        $regionId = Region::query()->where('uuid', $this->input('region'))->value('id');

        return [
            'region' => ['required', 'uuid', Rule::exists('regions', 'uuid')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:24', Rule::unique('districts', 'code')->ignore($district)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts', 'name')
                    ->where(fn ($query) => $query->where('region_id', $regionId))
                    ->ignore($district),
            ],
            'capital' => ['nullable', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:5000'],
            'readiness_score' => ['nullable', 'numeric', 'between:0,100'],
            'population' => ['nullable', 'integer', 'min:0'],
            'area_sq_km' => ['nullable', 'numeric', 'min:0'],
            'infrastructure_quality_score' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
