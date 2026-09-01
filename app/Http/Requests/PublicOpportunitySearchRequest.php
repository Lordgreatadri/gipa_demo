<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicOpportunitySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['query' => trim((string) $this->input('query')) ?: null]);
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'uuid'],
            'district' => ['nullable', 'uuid'],
            'sector' => ['nullable', 'uuid'],
            'type' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::in([
                Opportunity::WORKFLOW_APPROVED,
                Opportunity::WORKFLOW_ACTIVE,
                Opportunity::WORKFLOW_COMPLETED,
                Opportunity::WORKFLOW_CANCELLED,
            ])],
            'sort' => ['nullable', Rule::in(['newest', 'amount_asc', 'amount_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
