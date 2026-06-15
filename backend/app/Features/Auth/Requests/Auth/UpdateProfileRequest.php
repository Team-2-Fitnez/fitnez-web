<?php

namespace App\Features\Auth\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'age' => $this->filled('age') ? (int) $this->input('age') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                "regex:/^[\pL\pM\s.'-]+$/u",
            ],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'phone' => ['nullable', 'string', 'min:8', 'max:20', 'regex:/^[0-9+()\-\s]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.regex' => 'Full name may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'phone.regex' => 'Phone number may only contain digits, spaces, +, -, and parentheses.',
        ];
    }
}
