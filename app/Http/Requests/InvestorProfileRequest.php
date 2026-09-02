<?php

namespace App\Http\Requests;

use App\Models\InvestorProfile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->account_type === User::ACCOUNT_INVESTOR && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'profile_type' => ['required', Rule::in([InvestorProfile::TYPE_INDIVIDUAL, InvestorProfile::TYPE_ORGANIZATION_REPRESENTATIVE])],
            'display_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'nationality_country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'preferred_language' => ['required', Rule::in(['en'])],
            'preferred_contact_channel' => ['required', Rule::in(['email', 'phone'])],
        ];
    }
}