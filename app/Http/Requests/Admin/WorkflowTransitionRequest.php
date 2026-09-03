<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true;
    }

    public function rules(): array
    {
        $action = $this->route('action');

        return [
            'reviewer' => [
                Rule::requiredIf(in_array($action, ['submit', 'reassign'], true)),
                'nullable',
                'uuid',
                Rule::exists('users', 'uuid')->where(fn ($query) => $query
                    ->where('account_type', 'staff')
                    ->where('status', 'active')),
            ],
            'reason' => [
                Rule::requiredIf(in_array($action, ['reject', 'cancel'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
