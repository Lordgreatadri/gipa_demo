<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffDistrictAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Administrator') === true;
    }

    public function rules(): array
    {
        return [
            'officer' => ['required', 'uuid', Rule::exists('users', 'uuid')->where(fn ($query) => $query->where('account_type', 'staff')->where('status', 'active'))],
            'district' => ['required', 'uuid', Rule::exists('districts', 'uuid')->whereNull('deleted_at')],
            'starts_at' => ['required', 'date'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
