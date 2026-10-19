<?php

namespace App\Http\Requests;

use App\Services\Sms\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreContactEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'available_time_for_call' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'consent' => ['accepted'],
            // Honeypot: legitimate browsers leave this hidden field empty.
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $phone = (string) $this->input('phone_number', '');

            if ($phone !== '' && ! PhoneNumber::isValid($phone)) {
                $validator->errors()->add('phone_number', 'Enter a valid phone number, for example 024 123 4567.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please confirm you consent to GIPA using your details to respond.',
        ];
    }
}
