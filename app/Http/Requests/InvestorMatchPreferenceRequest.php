<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestorMatchPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->account_type === User::ACCOUNT_INVESTOR && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'sector_ids' => ['required', 'array', 'min:1', 'max:10'],
            'sector_ids.*' => ['integer', Rule::exists('sectors', 'id')->where('is_active', true)],
            'region_ids' => ['required', 'array', 'min:1', 'max:16'],
            'region_ids.*' => ['integer', Rule::exists('regions', 'id')->whereNull('deleted_at')],
            'minimum_investment' => ['nullable', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'maximum_investment' => ['nullable', 'numeric', 'gte:minimum_investment', 'max:9999999999999999.99'],
            'currency' => ['required', Rule::in(['GHS', 'USD', 'EUR', 'GBP'])],
            'minimum_readiness_score' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
