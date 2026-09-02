<?php

namespace App\Http\Requests\Admin;

use App\Models\CertificateVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CertificateVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('verify', $this->route('certificate')) === true;
    }

    public function rules(): array
    {
        return [
            'officer_decision' => ['required', Rule::in([
                CertificateVerification::DECISION_VALID,
                CertificateVerification::DECISION_SUSPICIOUS,
                CertificateVerification::DECISION_INVALID,
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy_metres' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'idempotency_key' => ['required', 'uuid'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }
}
