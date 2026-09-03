<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestorDocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->account_type === User::ACCOUNT_INVESTOR && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::exists('investor_document_types', 'code')->where('is_active', true)],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
