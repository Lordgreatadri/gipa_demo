<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicDistrictSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'uuid'],
            'readiness' => ['nullable', 'integer', 'between:0,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
