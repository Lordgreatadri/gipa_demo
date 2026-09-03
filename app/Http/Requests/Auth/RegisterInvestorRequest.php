<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'organization' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ];
    }
}
