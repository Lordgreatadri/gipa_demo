<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssistantChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:'.(int) config('assistant.guardrails.max_question_length', 1000)],
            'conversation' => ['nullable', 'string', 'size:36'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a question for the assistant.',
            'message.max' => 'Your question is too long. Please shorten it and try again.',
        ];
    }
}
