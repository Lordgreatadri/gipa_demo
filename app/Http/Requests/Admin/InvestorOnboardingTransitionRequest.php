<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestorOnboardingTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'reason' => [
                Rule::requiredIf(in_array($this->route('action'), ['request-changes', 'reject'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}